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
use App\Events\TransferCancelled;
use App\Events\TransferExecuted;
use App\Models\CapitalAccount;
use App\Models\CapitalTransaction;
use App\Models\ExchangeRate;
use App\Models\Payment;
use App\Models\ProfitAccountTransaction;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransferLifecycleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_receipt_is_idempotent_and_replays_the_saved_response(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $transfer = $this->createTransfer($user);
        $account = $this->createAccount(Branch::UAE, CurrencyType::USD, CapitalAccountType::CAPITAL, '0.00');
        $token = $user->createToken('coordinator')->plainTextToken;
        $headers = ['Idempotency-Key' => 'payment-key-123'];

        $first = $this->withToken($token)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/payments', [
                'amount' => '25.00',
                'notes' => 'First installment',
            ], $headers);

        $first->assertCreated()
            ->assertJsonPath('data.amount', '25.00')
            ->assertJsonPath('data.notes', 'First installment')
            ->assertJsonPath('data.received_by.id', $user->id);

        $paymentId = $first->json('data.id');
        $this->withToken($token)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/payments', [
                'amount' => '25.00',
                'notes' => 'First installment',
            ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.id', $paymentId);

        $this->assertSame(1, $transfer->payments()->count());
        $this->assertDatabaseCount('api_idempotency_keys', 1);
        $this->assertDatabaseHas('transfers', ['id' => $transfer->id, 'paid_amount' => '25.00']);
        $this->assertEquals(25, $account->fresh()->balance);
        $this->assertSame(1, CapitalTransaction::query()->where('reference_id', $transfer->id)->count());
    }

    public function test_reusing_payment_idempotency_key_with_different_payload_is_rejected(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $transfer = $this->createTransfer($user);
        $this->createAccount(Branch::UAE, CurrencyType::USD, CapitalAccountType::CAPITAL, '0.00');
        $token = $user->createToken('coordinator')->plainTextToken;
        $headers = ['Idempotency-Key' => 'payment-key-456'];

        $this->withToken($token)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/payments', ['amount' => '25.00'], $headers)
            ->assertCreated();

        $this->withToken($token)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/payments', ['amount' => '30.00'], $headers)
            ->assertConflict()
            ->assertJsonPath('error.code', 'IDEMPOTENCY_KEY_REUSED');

        $this->assertSame(1, $transfer->payments()->count());
        $this->assertDatabaseHas('transfers', ['id' => $transfer->id, 'paid_amount' => '25.00']);
    }

    public function test_overpayment_is_rejected_without_persisting_a_key_or_ledger_entry(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $transfer = $this->createTransfer($user);
        $this->createAccount(Branch::UAE, CurrencyType::USD, CapitalAccountType::CAPITAL, '0.00');

        $this->withToken($user->createToken('coordinator')->plainTextToken)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/payments', ['amount' => '101.00'], [
                'Idempotency-Key' => 'payment-overpay-key',
            ])
            ->assertConflict()
            ->assertJsonPath('error.code', 'PAYMENT_NOT_ALLOWED');

        $this->assertSame(0, $transfer->payments()->count());
        $this->assertDatabaseCount('api_idempotency_keys', 0);
        $this->assertSame(0, CapitalTransaction::query()->where('reference_id', $transfer->id)->count());
    }

    public function test_payment_requires_idempotency_key_and_is_limited_to_coordinators_and_admins(): void
    {
        $coordinator = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $executor = User::factory()->create(['role' => UserRole::EXECUTOR]);
        $transfer = $this->createTransfer($coordinator);

        $this->withToken($coordinator->createToken('coordinator')->plainTextToken)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/payments', ['amount' => '10.00'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('idempotency_key');

        $this->app['auth']->forgetGuards();
        $this->withToken($executor->createToken('executor')->plainTextToken)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/payments', ['amount' => '10.00'], [
                'Idempotency-Key' => 'payment-key-denied',
            ])
            ->assertForbidden();
    }

    public function test_executor_can_execute_transfer_and_upload_proof_with_ledger_entries(): void
    {
        Event::fake([TransferExecuted::class]);
        Storage::fake('public');
        $executor = User::factory()->create(['role' => UserRole::EXECUTOR]);
        $transfer = $this->createTransfer($executor, [
            'commission_amount' => '0.00',
        ]);
        $this->seedExchangeRates();
        $gazaCapital = $this->createAccount(Branch::GAZA, CurrencyType::USD, CapitalAccountType::CAPITAL, '500.00');
        $profit = $this->createAccount(Branch::GAZA, CurrencyType::USD, CapitalAccountType::PROFIT, '0.00');

        $response = $this->withToken($executor->createToken('executor')->plainTextToken)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/execute', [
                'proof' => UploadedFile::fake()->image('receipt.png'),
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.proof_available', true);

        $transfer->refresh();
        $this->assertSame(TransferStatus::COMPLETED, $transfer->status);
        $this->assertSame($executor->id, $transfer->completed_by);
        $this->assertEquals(400, $gazaCapital->fresh()->balance);
        $this->assertEquals(0, $profit->fresh()->balance);
        $this->assertSame(3, CapitalTransaction::query()->where('reference_id', $transfer->id)->count());
        $this->assertSame(1, ProfitAccountTransaction::query()->where('transfer_id', $transfer->id)->count());
        Storage::disk('public')->assertExists($transfer->transfer_proof_path);
        Event::assertDispatched(TransferExecuted::class);
    }

    public function test_failed_execution_removes_uploaded_proof_and_leaves_ledger_untouched(): void
    {
        Storage::fake('public');
        $executor = User::factory()->create(['role' => UserRole::TRANSFER_EXECUTOR]);
        $transfer = $this->createTransfer($executor, ['commission_amount' => '0.00']);
        $this->seedExchangeRates();
        $this->createAccount(Branch::GAZA, CurrencyType::USD, CapitalAccountType::CAPITAL, '10.00');
        $this->createAccount(Branch::GAZA, CurrencyType::USD, CapitalAccountType::PROFIT, '0.00');

        $this->withToken($executor->createToken('executor')->plainTextToken)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/execute', [
                'proof' => UploadedFile::fake()->image('receipt.png'),
            ])
            ->assertConflict()
            ->assertJsonPath('error.code', 'TRANSFER_EXECUTION_FAILED');

        $this->assertSame(TransferStatus::PENDING, $transfer->fresh()->status);
        $this->assertSame(0, CapitalTransaction::query()->where('reference_id', $transfer->id)->count());
        $this->assertSame(0, ProfitAccountTransaction::query()->where('transfer_id', $transfer->id)->count());
        $this->assertSame([], Storage::disk('public')->allFiles('transfer_proofs'));
    }

    public function test_only_executors_can_execute_and_invalid_proof_is_rejected(): void
    {
        Storage::fake('public');
        $coordinator = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $transfer = $this->createTransfer($coordinator);
        $coordinatorToken = $coordinator->createToken('coordinator')->plainTextToken;

        $this->withToken($coordinatorToken)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/execute', [
                'proof' => UploadedFile::fake()->image('receipt.png'),
            ])
            ->assertForbidden();

        $executor = User::factory()->create(['role' => UserRole::EXECUTOR]);
        $this->app['auth']->forgetGuards();
        $this->withToken($executor->createToken('executor')->plainTextToken)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/execute', [
                'proof' => UploadedFile::fake()->create('script.txt', 20, 'text/plain'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('proof');
    }

    public function test_pending_cancellation_reverses_received_payment_and_dispatches_event(): void
    {
        Event::fake([TransferCancelled::class]);
        $coordinator = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $transfer = $this->createTransfer($coordinator, [
            'paid_amount' => '20.00',
            'remaining_amount' => '80.00',
            'payment_status' => PaymentStatus::PARTIALLY_PAID->value,
        ]);
        $account = $this->createAccount(Branch::UAE, CurrencyType::USD, CapitalAccountType::CAPITAL, '20.00');
        Payment::query()->create([
            'transfer_id' => $transfer->id,
            'amount' => '20.00',
            'currency' => CurrencyType::USD,
            'received_by' => $coordinator->id,
            'received_at' => now(),
        ]);

        $this->withToken($coordinator->createToken('coordinator')->plainTextToken)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(TransferStatus::CANCELLED, $transfer->fresh()->status);
        $this->assertSame($coordinator->id, $transfer->fresh()->cancelled_by);
        $this->assertSame(0, $transfer->payments()->count());
        $this->assertEquals(0, $account->fresh()->balance);
        $this->assertDatabaseHas('capital_transactions', [
            'capital_account_id' => $account->id,
            'transaction_type' => 'transfer_cancellation',
            'created_by' => $coordinator->id,
        ]);
        Event::assertDispatched(TransferCancelled::class);
    }

    public function test_force_cancel_requires_admin_password_and_reverses_completed_ledger(): void
    {
        Storage::fake('public');
        $executor = User::factory()->create(['role' => UserRole::EXECUTOR]);
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'password' => 'admin-secret',
        ]);
        $this->seedExchangeRates();
        $transfer = $this->createTransfer($executor, ['commission_amount' => '0.00']);
        $gazaCapital = $this->createAccount(Branch::GAZA, CurrencyType::USD, CapitalAccountType::CAPITAL, '500.00');
        $profit = $this->createAccount(Branch::GAZA, CurrencyType::USD, CapitalAccountType::PROFIT, '0.00');

        $this->withToken($executor->createToken('executor')->plainTextToken)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/execute', [
                'proof' => UploadedFile::fake()->image('receipt.png'),
            ])
            ->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/force-cancel', [
                'password' => 'wrong-password',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'INVALID_PASSWORD');

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/force-cancel', ['password' => 'admin-secret'])
            ->assertUnprocessable()->assertJsonValidationErrors('notes');

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/force-cancel', ['password' => 'admin-secret', 'notes' => '   '])
            ->assertUnprocessable()->assertJsonValidationErrors('notes');

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->postJson('/api/v1/transfers/'.$transfer->id.'/force-cancel', [
                'password' => 'admin-secret',
                'notes' => 'Reversal approved',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(TransferStatus::CANCELLED, $transfer->fresh()->status);
        $this->assertSame($admin->id, $transfer->fresh()->cancelled_by);
        $this->assertEquals(500, $gazaCapital->fresh()->balance);
        $this->assertEquals(0, $profit->fresh()->balance);
        $this->assertSame(0, ProfitAccountTransaction::query()->where('transfer_id', $transfer->id)->count());
        $this->assertSame(3, CapitalTransaction::query()
            ->where('reference_id', $transfer->id)
            ->where('transaction_type', 'post_completion_cancellation')
            ->count());
    }

    private function seedExchangeRates(): void
    {
        ExchangeRate::query()->create(['currency' => CurrencyType::USD, 'rate_to_usd' => '1.00000000']);
        ExchangeRate::query()->create(['currency' => CurrencyType::AED, 'rate_to_usd' => '0.27229400']);
        ExchangeRate::query()->create(['currency' => CurrencyType::ILS, 'rate_to_usd' => '0.26954100']);
    }

    private function createAccount(Branch $branch, CurrencyType $currency, CapitalAccountType $type, string $balance): CapitalAccount
    {
        return CapitalAccount::query()->create([
            'name' => $branch->value.' '.$currency->value.' '.$type->value,
            'branch' => $branch,
            'currency' => $currency,
            'account_type' => $type,
            'balance' => $balance,
            'is_active' => true,
        ]);
    }

    private function createTransfer(User $creator, array $overrides = []): Transfer
    {
        return Transfer::query()->forceCreate(array_merge([
            'reference_number' => 'TRF-'.strtoupper(fake()->unique()->lexify('??????')),
            'receiver_name' => 'Lifecycle Recipient',
            'receiver_method' => ReceiverMethod::BANK->value,
            'receiver_account_number' => '987654321',
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
