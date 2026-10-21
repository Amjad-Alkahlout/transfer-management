<?php

namespace Tests\Feature\Api\V1;

use App\Enums\Branch;
use App\Enums\CapitalAccountType;
use App\Enums\CapitalTransactionType;
use App\Enums\CurrencyType;
use App\Enums\TransactionDirection;
use App\Enums\UserRole;
use App\Models\CapitalAccount;
use App\Models\CapitalTransaction;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class CapitalAndUsersApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_capital_account_creation_records_opening_balance_and_rejects_duplicate_dimensions(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $token = $admin->createToken('admin')->plainTextToken;
        $payload = ['name' => 'UAE USD', 'branch' => 'uae', 'currency' => 'usd', 'account_type' => 'capital', 'opening_balance' => '100.00'];

        $this->withToken($token)->postJson('/api/v1/capital-accounts', $payload)->assertCreated()->assertJsonPath('data.balance', '100.00');
        $this->assertDatabaseHas('capital_transactions', ['transaction_type' => CapitalTransactionType::OPENING_BALANCE->value, 'amount' => '100.00', 'created_by' => $admin->id]);
        $this->withToken($token)->postJson('/api/v1/capital-accounts', $payload)->assertUnprocessable();
    }

    public function test_account_adjustment_and_profit_withdrawal_keep_ledger_and_actor(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $token = $admin->createToken('admin')->plainTextToken;
        $account = $this->account('uae', 'usd', 'capital', '100.00');

        $this->withToken($token)->postJson("/api/v1/capital-accounts/{$account->id}/adjustments", ['direction' => 'out', 'amount' => '15.25', 'notes' => 'Correction'], ['Idempotency-Key' => 'adjust-key-1'])
            ->assertOk()->assertJsonPath('data.balance', '84.75');
        $this->withToken($token)->postJson("/api/v1/capital-accounts/{$account->id}/adjustments", ['direction' => 'out', 'amount' => '15.25', 'notes' => 'Correction'], ['Idempotency-Key' => 'adjust-key-1'])
            ->assertOk()->assertJsonPath('data.balance', '84.75');
        $this->assertSame(1, CapitalTransaction::query()->where('capital_account_id', $account->id)->count());
        $this->assertDatabaseHas('capital_transactions', ['capital_account_id' => $account->id, 'transaction_type' => 'manual_adjustment', 'direction' => 'out', 'created_by' => $admin->id, 'notes' => 'Correction']);

        $profit = $this->account('gaza', 'usd', 'profit', '50.00');
        $this->withToken($token)->postJson("/api/v1/capital-accounts/{$profit->id}/profit-withdrawals", ['amount' => '20.00'], ['Idempotency-Key' => 'withdraw-key-1'])
            ->assertOk()->assertJsonPath('data.balance', '30.00');
        $this->assertDatabaseHas('capital_transactions', ['capital_account_id' => $profit->id, 'transaction_type' => 'profit_withdrawal', 'created_by' => $admin->id]);

        $this->withToken($token)->postJson("/api/v1/capital-accounts/{$account->id}/adjustments", ['direction' => 'out', 'amount' => '1000.00', 'notes' => 'Too much'], ['Idempotency-Key' => 'adjust-key-2'])->assertConflict();
    }

    public function test_capital_account_operations_follow_existing_gate_permissions(): void
    {
        $coordinator = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $executor = User::factory()->create(['role' => UserRole::EXECUTOR]);
        $transferExecutor = User::factory()->create(['role' => UserRole::TRANSFER_EXECUTOR]);
        $account = $this->account('uae', 'usd', 'capital', '20.00');

        $this->withToken($coordinator->createToken('c')->plainTextToken)->getJson('/api/v1/capital-accounts')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($transferExecutor->createToken('t')->plainTextToken)->getJson('/api/v1/capital-accounts')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->withToken($coordinator->createToken('c2')->plainTextToken)->patchJson("/api/v1/capital-accounts/{$account->id}", ['is_active' => false])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($transferExecutor->createToken('t2')->plainTextToken)->postJson("/api/v1/capital-accounts/{$account->id}/adjustments", ['direction' => 'in', 'amount' => 1, 'notes' => 'x'])->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->withToken($executor->createToken('e')->plainTextToken)->putJson('/api/v1/exchange-rates/usd', ['rate_to_usd' => '1.00000000'])->assertUnprocessable();
    }

    public function test_exchange_rate_update_is_executor_only_and_validated(): void
    {
        $this->seedRates();
        $executor = User::factory()->create(['role' => UserRole::EXECUTOR]);
        $coordinator = User::factory()->create(['role' => UserRole::COORDINATOR]);

        $this->withToken($executor->createToken('e')->plainTextToken)->putJson('/api/v1/exchange-rates/aed', ['rate_to_usd' => '0.30000000'])
            ->assertOk()->assertJsonPath('data.rate_to_usd', '0.30000000');
        $this->withToken($executor->createToken('e2')->plainTextToken)->putJson('/api/v1/exchange-rates/usd', ['rate_to_usd' => '0'])->assertUnprocessable();
        $this->app['auth']->forgetGuards();
        $this->withToken($coordinator->createToken('c')->plainTextToken)->putJson('/api/v1/exchange-rates/usd', ['rate_to_usd' => '1.00000000'])->assertForbidden();
    }

    public function test_capital_transfer_uses_existing_service_and_reports_ledger_entries(): void
    {
        $coordinator = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $from = $this->account('uae', 'usd', 'capital', '200.00');
        $to = $this->account('gaza', 'ils', 'capital', '0.00');
        $this->account('gaza', 'ils', 'profit', '100.00');
        $this->seedRates();
        $token = $coordinator->createToken('coordinator')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/capital-transfers/preview', ['from_account_id' => $from->id, 'to_account_id' => $to->id, 'source_amount' => '50.00', 'transfer_cost' => '2.00'])->assertOk();
        $transferPayload = ['from_account_id' => $from->id, 'to_account_id' => $to->id, 'source_amount' => '50.00', 'transfer_cost' => '2.00', 'notes' => 'test'];
        $result = $this->withToken($token)->postJson('/api/v1/capital-transfers', $transferPayload, ['Idempotency-Key' => 'capital-transfer-key'])->assertCreated();
        $this->withToken($token)->postJson('/api/v1/capital-transfers', $transferPayload, ['Idempotency-Key' => 'capital-transfer-key'])->assertCreated()->assertJsonPath('data.id', $result->json('data.id'));
        $this->assertDatabaseHas('capital_accounts', ['id' => $from->id, 'balance' => '150.00']);
        $this->assertDatabaseHas('capital_accounts', ['id' => $to->id, 'balance' => $result->json('data.destination_amount')]);
        $this->assertSame(3, CapitalTransaction::query()->count());
        $this->assertDatabaseHas('capital_transfers', ['created_by' => $coordinator->id, 'notes' => 'test']);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/capital-transfers?per_page=1')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $result->json('data.id'));
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/v1/capital-transfers', [...$transferPayload, 'source_amount' => '60.00'], ['Idempotency-Key' => 'capital-transfer-key'])->assertConflict();
    }

    public function test_admin_user_management_uses_safe_serialization_password_reset_and_self_protection(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $target = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $token = $admin->createToken('admin')->plainTextToken;
        $targetToken = $target->createToken('target')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/users')->assertOk()->assertJsonMissingPath('data.0.password')->assertJsonMissingPath('data.0.remember_token')->assertJsonMissingPath('data.0.telegram_link_code');
        $created = $this->withToken($token)->postJson('/api/v1/users', ['name' => 'New User', 'email' => 'new@example.test', 'role' => 'executor', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertCreated();
        $this->assertDatabaseHas('users', ['id' => $created->json('data.id'), 'is_active' => true]);

        $this->withToken($token)->putJson("/api/v1/users/{$target->id}/password", ['password' => 'changed-password', 'password_confirmation' => 'changed-password'])->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $target->id]);
        $this->withToken($token)->getJson("/api/v1/users/{$target->id}")->assertOk()->assertJsonPath('data.id', $target->id);
        $this->withToken($token)->patchJson("/api/v1/users/{$target->id}", ['name' => 'Updated User', 'is_active' => true])->assertOk()->assertJsonPath('data.name', 'Updated User');
        $this->withToken($token)->patchJson("/api/v1/users/{$admin->id}", ['is_active' => false])->assertUnprocessable();
        $this->withToken($token)->deleteJson("/api/v1/users/{$admin->id}")->assertUnprocessable();
        $this->withToken($token)->deleteJson("/api/v1/users/{$target->id}")->assertOk();
    }

    public function test_users_and_capital_ledger_are_admin_only(): void
    {
        $coordinator = User::factory()->create(['role' => UserRole::COORDINATOR]);
        $token = $coordinator->createToken('coordinator')->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/users')->assertForbidden();
        $this->withToken($token)->getJson('/api/v1/capital-transactions')->assertForbidden();
    }

    public function test_capital_ledger_filters_and_paginates_without_internal_reference_fields(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $account = $this->account('uae', 'usd', 'capital', '15.00');
        CapitalTransaction::query()->create(['capital_account_id' => $account->id, 'amount' => '15.00', 'direction' => TransactionDirection::IN, 'transaction_type' => CapitalTransactionType::OPENING_BALANCE, 'balance_before' => '0.00', 'balance_after' => '15.00', 'created_by' => $admin->id]);
        $this->withToken($admin->createToken('admin')->plainTextToken)->getJson('/api/v1/capital-transactions?type=opening_balance&per_page=1')
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.amount', '15.00')->assertJsonMissingPath('data.0.reference_type');
    }

    private function account(string $branch, string $currency, string $type, string $balance): CapitalAccount
    {
        return CapitalAccount::query()->create(['name' => strtoupper($branch.' '.$currency.' '.$type), 'branch' => Branch::from($branch), 'currency' => CurrencyType::from($currency), 'account_type' => CapitalAccountType::from($type), 'balance' => $balance, 'is_active' => true]);
    }

    private function seedRates(): void
    {
        ExchangeRate::query()->create(['currency' => CurrencyType::USD, 'rate_to_usd' => '1.00000000']);
        ExchangeRate::query()->create(['currency' => CurrencyType::AED, 'rate_to_usd' => '0.27229400']);
        ExchangeRate::query()->create(['currency' => CurrencyType::ILS, 'rate_to_usd' => '0.26954100']);
    }
}
