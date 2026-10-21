<?php

namespace Tests\Feature\Api\V1;

use App\Enums\Branch;
use App\Enums\CapitalAccountType;
use App\Enums\CurrencyType;
use App\Enums\PaymentStatus;
use App\Enums\ReceiverMethod;
use App\Enums\TransferCalculationMode;
use App\Enums\TransferStatus;
use App\Enums\UserRole;
use App\Events\TransferCreated;
use App\Models\CapitalAccount;
use App\Models\ExchangeRate;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class TransferMutationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinator_can_create_transfer_and_record_initial_payment_once(): void
    {
        Event::fake([TransferCreated::class]);
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $this->seedExchangeRates();
        $account = $this->createUaeCapitalAccount(CurrencyType::USD, '0.00');

        $response = $this->withToken($user->createToken('coordinator')->plainTextToken)
            ->postJson('/api/v1/transfers', $this->receiverAmountPayload([
                'initial_customer_pay_amount' => '25.00',
            ]), ['Idempotency-Key' => 'create-initial-payment-1']);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.customer_payment.paid_amount', '25.00')
            ->assertJsonPath('data.customer_payment.remaining_amount', '75.00')
            ->assertJsonPath('data.creator.id', $user->id);

        $transfer = Transfer::query()->findOrFail($response->json('data.id'));
        $this->assertMatchesRegularExpression('/^TRF-[A-Z0-9]{6}$/', $transfer->reference_number);
        $this->assertSame($user->id, $transfer->created_by);
        $this->assertSame(1, $transfer->payments()->count());
        $this->assertEquals(25, $transfer->payments()->first()->amount);
        $this->assertSame('25.00', $account->fresh()->balance);
        $this->assertDatabaseHas('capital_transactions', [
            'capital_account_id' => $account->id,
            'amount' => '25.00',
            'created_by' => $user->id,
        ]);
        Event::assertDispatched(TransferCreated::class, fn (TransferCreated $event): bool => $event->transfer->is($transfer));
    }

    public function test_create_rejects_invalid_calculation_or_initial_payment_without_persisting(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $this->seedExchangeRates();
        $token = $user->createToken('coordinator')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/transfers', $this->receiverAmountPayload([
                'initial_customer_pay_amount' => '101.00',
            ]), ['Idempotency-Key' => 'create-invalid-initial-1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('initial_customer_pay_amount');

        $this->assertDatabaseCount('transfers', 0);

        $this->withToken($token)
            ->postJson('/api/v1/transfers', $this->customerPaymentPayload([
                'customer_payable_amount' => '2.00',
                'customer_payable_currency' => CurrencyType::AED->value,
                'commission_amount' => '10.00',
            ]), ['Idempotency-Key' => 'create-invalid-commission-1'])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'INVALID_COMMISSION');

        $this->assertDatabaseCount('transfers', 0);
    }

    public function test_transfer_creation_replays_idempotent_response_and_rejects_payload_changes(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $this->seedExchangeRates();
        $token = $user->createToken('coordinator')->plainTextToken;
        $headers = ['Idempotency-Key' => 'transfer-create-replay-1'];
        $payload = $this->receiverAmountPayload();

        $first = $this->withToken($token)->postJson('/api/v1/transfers', $payload, $headers)->assertCreated();
        $transferId = $first->json('data.id');
        $this->withToken($token)->postJson('/api/v1/transfers', $payload, $headers)
            ->assertCreated()->assertJsonPath('data.id', $transferId);

        $this->withToken($token)->postJson('/api/v1/transfers', [...$payload, 'receiver_name' => 'Different recipient'], $headers)
            ->assertConflict()->assertJsonPath('error.code', 'IDEMPOTENCY_KEY_REUSED');

        $this->assertDatabaseCount('transfers', 1);
        $this->assertDatabaseCount('api_idempotency_keys', 1);
    }

    public function test_transfer_create_and_update_return_service_unavailable_when_rates_are_missing(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $token = $user->createToken('coordinator')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/transfers', $this->receiverAmountPayload(), ['Idempotency-Key' => 'missing-rate-create'])
            ->assertServiceUnavailable()->assertJsonPath('error.code', 'EXCHANGE_RATES_UNAVAILABLE');
        $this->assertDatabaseCount('transfers', 0);
        $this->assertDatabaseCount('api_idempotency_keys', 0);

        $transfer = $this->createPendingTransfer($user);
        $this->withToken($token)->patchJson('/api/v1/transfers/'.$transfer->id, $this->receiverAmountPayload())
            ->assertServiceUnavailable()->assertJsonPath('error.code', 'EXCHANGE_RATES_UNAVAILABLE');
        $this->assertDatabaseHas('transfers', ['id' => $transfer->id, 'receiver_name' => 'Existing Recipient']);
    }

    public function test_create_rolls_back_transfer_when_initial_payment_cannot_be_recorded(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $this->seedExchangeRates();

        $this->withToken($user->createToken('coordinator')->plainTextToken)
            ->postJson('/api/v1/transfers', $this->receiverAmountPayload([
                'initial_customer_pay_amount' => '25.00',
            ]), ['Idempotency-Key' => 'create-missing-capital-account-1'])
            ->assertNotFound();

        $this->assertDatabaseCount('transfers', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_only_coordinators_and_admins_can_create_transfers(): void
    {
        $executor = User::factory()->create(['role' => UserRole::EXECUTOR]);

        $this->withToken($executor->createToken('executor')->plainTextToken)
            ->postJson('/api/v1/transfers', $this->receiverAmountPayload())
            ->assertForbidden();
    }

    public function test_coordinator_can_update_only_pending_unpaid_transfer(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $this->seedExchangeRates();
        $transfer = $this->createPendingTransfer($user);
        $token = $user->createToken('coordinator')->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/v1/transfers/'.$transfer->id, $this->receiverAmountPayload([
                'receiver_name' => 'Updated Recipient',
                'receiver_method' => ReceiverMethod::WALLET->value,
                'receiver_wallet_phone' => '+970555000000',
                'receiver_account_number' => null,
                'requested_amount' => '125.00',
                'notes' => 'Updated instruction',
            ]))
            ->assertOk()
            ->assertJsonPath('data.receiver.name', 'Updated Recipient')
            ->assertJsonPath('data.receiver.method', 'wallet')
            ->assertJsonPath('data.receiver.account_number', null)
            ->assertJsonPath('data.payout.amount', '125.00')
            ->assertJsonPath('data.customer_payment.remaining_amount', '125.00');

        $this->assertDatabaseHas('transfers', [
            'id' => $transfer->id,
            'receiver_wallet_phone' => '+970555000000',
            'receiver_account_number' => null,
            'requested_amount' => '125.00',
        ]);
    }

    public function test_update_rejects_paid_or_non_pending_transfers_with_conflict(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $this->seedExchangeRates();
        $token = $user->createToken('coordinator')->plainTextToken;

        $paid = $this->createPendingTransfer($user, [
            'payment_status' => PaymentStatus::PARTIALLY_PAID->value,
            'paid_amount' => '20.00',
            'remaining_amount' => '80.00',
        ]);
        $this->withToken($token)
            ->patchJson('/api/v1/transfers/'.$paid->id, $this->receiverAmountPayload())
            ->assertConflict()
            ->assertJsonPath('error.code', 'TRANSFER_NOT_EDITABLE');

        $completed = $this->createPendingTransfer($user, [
            'status' => TransferStatus::COMPLETED->value,
        ]);
        $this->withToken($token)
            ->patchJson('/api/v1/transfers/'.$completed->id, $this->receiverAmountPayload())
            ->assertConflict()
            ->assertJsonPath('error.code', 'TRANSFER_NOT_EDITABLE');
    }

    public function test_only_coordinators_and_admins_can_update_transfers(): void
    {
        $coordinator = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $executor = User::factory()->create(['role' => UserRole::EXECUTOR]);
        $transfer = $this->createPendingTransfer($coordinator);

        $this->withToken($executor->createToken('executor')->plainTextToken)
            ->patchJson('/api/v1/transfers/'.$transfer->id, $this->receiverAmountPayload())
            ->assertForbidden();
    }

    public function test_livewire_creation_uses_the_shared_transfer_service(): void
    {
        Event::fake([TransferCreated::class]);
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $this->seedExchangeRates();

        Livewire::actingAs($user)
            ->test('pages::transfers.create')
            ->set('receiver_name', 'Web Recipient')
            ->set('receiver_method', ReceiverMethod::BANK->value)
            ->set('receiver_account_number', '12345678')
            ->set('requested_amount', '40.00')
            ->set('requested_currency', CurrencyType::USD->value)
            ->set('customer_payable_currency', CurrencyType::USD->value)
            ->set('commission_amount', '0.00')
            ->set('initial_customer_pay_amount', '0.00')
            ->call('createTransfer')
            ->assertRedirect();

        $transfer = Transfer::query()->where('receiver_name', 'Web Recipient')->firstOrFail();
        $this->assertSame($user->id, $transfer->created_by);
        $this->assertEquals(40, $transfer->transfer_amount);
        Event::assertDispatched(TransferCreated::class);
    }

    public function test_livewire_update_uses_the_shared_transfer_service(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $this->seedExchangeRates();
        $transfer = $this->createPendingTransfer($user);

        Livewire::actingAs($user)
            ->test('pages::transfers.edit', ['transfer' => $transfer])
            ->set('receiver_name', 'Web Updated Recipient')
            ->set('requested_amount', '80.00')
            ->call('updateTransfer')
            ->assertRedirect();

        $this->assertDatabaseHas('transfers', [
            'id' => $transfer->id,
            'receiver_name' => 'Web Updated Recipient',
            'requested_amount' => '80.00',
            'transfer_amount' => '80.00',
        ]);
    }

    private function seedExchangeRates(): void
    {
        ExchangeRate::query()->create(['currency' => CurrencyType::USD, 'rate_to_usd' => '1.00000000']);
        ExchangeRate::query()->create(['currency' => CurrencyType::AED, 'rate_to_usd' => '0.27229400']);
        ExchangeRate::query()->create(['currency' => CurrencyType::ILS, 'rate_to_usd' => '0.26954100']);
    }

    private function createUaeCapitalAccount(CurrencyType $currency, string $balance): CapitalAccount
    {
        return CapitalAccount::query()->create([
            'name' => 'UAE '.$currency->value.' Capital',
            'branch' => Branch::UAE,
            'currency' => $currency,
            'account_type' => CapitalAccountType::CAPITAL,
            'balance' => $balance,
            'is_active' => true,
        ]);
    }

    private function receiverAmountPayload(array $overrides = []): array
    {
        return array_merge([
            'receiver_name' => 'New Recipient',
            'receiver_method' => ReceiverMethod::BANK->value,
            'receiver_account_number' => '12345678',
            'requested_amount' => '100.00',
            'requested_currency' => CurrencyType::USD->value,
            'customer_payable_currency' => CurrencyType::USD->value,
            'commission_amount' => '0.00',
            'calculation_mode' => TransferCalculationMode::RECEIVER_AMOUNT->value,
        ], $overrides);
    }

    private function customerPaymentPayload(array $overrides = []): array
    {
        return array_merge([
            'receiver_name' => 'New Recipient',
            'receiver_method' => ReceiverMethod::BANK->value,
            'receiver_account_number' => '12345678',
            'requested_currency' => CurrencyType::USD->value,
            'customer_payable_amount' => '100.00',
            'customer_payable_currency' => CurrencyType::USD->value,
            'commission_amount' => '5.00',
            'calculation_mode' => TransferCalculationMode::CUSTOMER_PAYMENT->value,
        ], $overrides);
    }

    private function createPendingTransfer(User $creator, array $overrides = []): Transfer
    {
        return Transfer::query()->forceCreate(array_merge([
            'reference_number' => 'TRF-'.strtoupper(fake()->unique()->lexify('??????')),
            'receiver_name' => 'Existing Recipient',
            'receiver_method' => ReceiverMethod::BANK->value,
            'receiver_account_number' => '87654321',
            'requested_amount' => '100.00',
            'requested_currency' => CurrencyType::USD->value,
            'transfer_amount' => '100.00',
            'customer_payable_amount' => '100.00',
            'customer_payable_currency' => CurrencyType::USD->value,
            'commission_amount' => '0.00',
            'commission_currency' => CurrencyType::AED->value,
            'paid_amount' => '0.00',
            'remaining_amount' => '100.00',
            'payment_status' => PaymentStatus::UNPAID->value,
            'calculation_mode' => TransferCalculationMode::RECEIVER_AMOUNT->value,
            'status' => TransferStatus::PENDING->value,
            'created_by' => $creator->id,
        ], $overrides));
    }
}
