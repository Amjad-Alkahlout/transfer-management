<?php

namespace Tests\Feature\Api\V1;

use App\Enums\CurrencyType;
use App\Enums\PaymentStatus;
use App\Enums\TransferCalculationMode;
use App\Enums\TransferStatus;
use App\Enums\UserRole;
use App\Models\CapitalAccount;
use App\Models\ExchangeRate;
use App\Models\Payment;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_index_filters_and_paginates_results(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $matching = $this->createTransfer($user, [
            'reference_number' => 'TRF-ONE123',
            'status' => TransferStatus::PENDING,
            'payment_status' => PaymentStatus::PARTIALLY_PAID,
        ]);
        $this->createTransfer($user, [
            'reference_number' => 'TRF-TWO456',
            'status' => TransferStatus::COMPLETED,
            'payment_status' => PaymentStatus::PAID,
        ]);

        $this->withToken($user->createToken('reader')->plainTextToken)
            ->getJson('/api/v1/transfers?status=pending&payment_status=partially_paid&reference=ONE&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.payout.amount', '100.00')
            ->assertJsonPath('data.0.payout.currency', 'usd');
    }

    public function test_cancelled_transfers_are_excluded_from_payment_status_filter(): void
    {
        $user = User::factory()->create();
        $this->createTransfer($user, [
            'reference_number' => 'TRF-CANCEL1',
            'status' => TransferStatus::CANCELLED,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        $this->withToken($user->createToken('reader')->plainTextToken)
            ->getJson('/api/v1/transfers?payment_status=unpaid')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_transfer_filters_reject_invalid_values_and_page_sizes_over_one_hundred(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('reader')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/transfers?status=unknown')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->withToken($token)
            ->getJson('/api/v1/transfers?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');

        $this->withToken($token)
            ->getJson('/api/v1/transfers?created_from=2026-10-20&created_to=2026-10-19')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('created_to');
    }

    public function test_transfer_index_filters_by_created_date_range(): void
    {
        $user = User::factory()->create();
        $this->createTransfer($user, [
            'reference_number' => 'TRF-OLDER1',
            'created_at' => now()->subDays(3),
        ]);
        $recent = $this->createTransfer($user, [
            'reference_number' => 'TRF-RECENT',
            'created_at' => now()->subDay(),
        ]);

        $this->withToken($user->createToken('reader')->plainTextToken)
            ->getJson('/api/v1/transfers?created_from='.now()->subDay()->toDateString().'&created_to='.now()->toDateString())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $recent->id)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_transfer_detail_returns_payment_history_without_internal_proof_path(): void
    {
        $user = User::factory()->create();
        $receiver = User::factory()->create();
        $transfer = $this->createTransfer($user, [
            'transfer_proof_path' => 'transfer_proofs/private-receipt.png',
        ]);
        Payment::query()->create([
            'transfer_id' => $transfer->id,
            'amount' => 20,
            'currency' => CurrencyType::USD,
            'notes' => 'Collected at office',
            'received_by' => $receiver->id,
            'received_at' => now(),
        ]);

        $this->withToken($user->createToken('reader')->plainTextToken)
            ->getJson('/api/v1/transfers/'.$transfer->id)
            ->assertOk()
            ->assertJsonPath('data.id', $transfer->id)
            ->assertJsonPath('data.proof_available', true)
            ->assertJsonPath('data.payments.0.amount', '20.00')
            ->assertJsonPath('data.payments.0.received_by.name', $receiver->name)
            ->assertJsonMissingPath('data.transfer_proof_path');
    }

    public function test_calculation_endpoint_supports_both_calculation_modes(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $this->seedExchangeRates();
        $token = $user->createToken('calculator')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/transfers/calculate', [
                'calculation_mode' => TransferCalculationMode::RECEIVER_AMOUNT->value,
                'requested_amount' => '100.00',
                'requested_currency' => CurrencyType::USD->value,
                'customer_payable_currency' => CurrencyType::AED->value,
                'commission_amount' => '10.00',
            ])
            ->assertOk()
            ->assertJsonPath('data.calculation_mode', 'receiver_amount')
            ->assertJsonPath('data.transfer_amount', '100.00')
            ->assertJsonPath('data.transfer_currency', 'usd')
            ->assertJsonPath('data.commission_currency', 'aed');

        $this->withToken($token)
            ->postJson('/api/v1/transfers/calculate', [
                'calculation_mode' => TransferCalculationMode::CUSTOMER_PAYMENT->value,
                'requested_currency' => CurrencyType::USD->value,
                'customer_payable_amount' => '100.00',
                'customer_payable_currency' => CurrencyType::AED->value,
                'commission_amount' => '10.00',
            ])
            ->assertOk()
            ->assertJsonPath('data.calculation_mode', 'customer_payment')
            ->assertJsonPath('data.transfer_currency', 'usd')
            ->assertJsonPath('data.customer_payable_amount', '100.00');
    }

    public function test_calculation_validates_input_and_reports_invalid_commission(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $this->seedExchangeRates();
        $token = $user->createToken('calculator')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/transfers/calculate', [
                'calculation_mode' => 'unknown',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('calculation_mode');

        $this->withToken($token)
            ->postJson('/api/v1/transfers/calculate', [
                'calculation_mode' => TransferCalculationMode::CUSTOMER_PAYMENT->value,
                'requested_currency' => CurrencyType::USD->value,
                'customer_payable_amount' => '10.00',
                'customer_payable_currency' => CurrencyType::AED->value,
                'commission_amount' => '10.00',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'INVALID_COMMISSION');
    }

    public function test_dashboard_and_rates_return_currency_safe_data_for_allowed_roles(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $this->seedExchangeRates();
        CapitalAccount::query()->create([
            'name' => 'Gaza Capital USD',
            'branch' => 'gaza',
            'currency' => 'usd',
            'account_type' => 'capital',
            'balance' => '125.50',
            'is_active' => true,
        ]);
        $this->createTransfer($user, [
            'customer_payable_currency' => CurrencyType::AED,
            'remaining_amount' => '40.25',
        ]);

        $token = $user->createToken('dashboard')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.financial_overview.gaza_capital_usd', '125.50')
            ->assertJsonPath('data.financial_overview.outstanding_receivables.0.currency', 'aed')
            ->assertJsonPath('data.financial_overview.outstanding_receivables.0.amount', '40.25')
            ->assertJsonPath('data.transfer_counts.pending', 1);

        $this->withToken($token)
            ->getJson('/api/v1/exchange-rates')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.currency', 'aed')
            ->assertJsonPath('data.0.rate_to_usd', '0.27229400');
    }

    public function test_transfer_executor_can_read_transfers_but_not_financial_summary_or_rates(): void
    {
        $user = User::factory()->create(['role' => UserRole::TRANSFER_EXECUTOR]);
        $token = $user->createToken('transfer-executor')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/transfers')
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/v1/dashboard/summary')
            ->assertForbidden();

        $this->withToken($token)
            ->getJson('/api/v1/exchange-rates')
            ->assertForbidden();

        $this->withToken($token)
            ->postJson('/api/v1/transfers/calculate', [])
            ->assertForbidden();
    }

    private function seedExchangeRates(): void
    {
        ExchangeRate::query()->create(['currency' => CurrencyType::USD, 'rate_to_usd' => '1.00000000']);
        ExchangeRate::query()->create(['currency' => CurrencyType::AED, 'rate_to_usd' => '0.27229400']);
        ExchangeRate::query()->create(['currency' => CurrencyType::ILS, 'rate_to_usd' => '0.26954100']);
    }

    private function createTransfer(User $creator, array $overrides = []): Transfer
    {
        return Transfer::query()->forceCreate(array_merge([
            'reference_number' => 'TRF-'.strtoupper(fake()->unique()->lexify('??????')),
            'receiver_name' => 'Test Recipient',
            'receiver_method' => 'bank',
            'receiver_account_number' => '123456789',
            'requested_amount' => '100.00',
            'requested_currency' => CurrencyType::USD->value,
            'transfer_amount' => '100.00',
            'customer_payable_amount' => '105.00',
            'customer_payable_currency' => CurrencyType::USD->value,
            'commission_amount' => '5.00',
            'commission_currency' => CurrencyType::AED->value,
            'paid_amount' => '20.00',
            'remaining_amount' => '85.00',
            'payment_status' => PaymentStatus::PARTIALLY_PAID->value,
            'calculation_mode' => TransferCalculationMode::RECEIVER_AMOUNT->value,
            'status' => TransferStatus::PENDING->value,
            'created_by' => $creator->id,
        ], $overrides));
    }
}
