<?php

namespace Tests\Feature;

use App\Enums\Branch;
use App\Enums\CapitalAccountType;
use App\Enums\CurrencyType;
use App\Enums\UserRole;
use App\Models\CapitalAccount;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CapitalTransferModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_clearing_transfer_cost_keeps_preview_safe_while_editing(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        [$from, $to] = $this->accountsAndRates();

        $component = $this->actingAs($user)->capitalTransferComponent($from, $to)
            ->set('transfer_cost', '');

        $component->assertSet('transfer_cost', '')
            ->assertSet('preview.total_deduction', 50.0)
            ->assertSee('0.00');
    }

    public function test_valid_and_zero_transfer_costs_update_the_preview(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        [$from, $to] = $this->accountsAndRates();
        $component = $this->actingAs($user)->capitalTransferComponent($from, $to);

        $component->set('transfer_cost', '2.50')
            ->assertSet('preview.total_deduction', 52.5)
            ->assertSee('2.50');

        $component->set('transfer_cost', '0')
            ->assertSet('preview.total_deduction', 50.0)
            ->assertSee('0.00');
    }

    public function test_invalid_transfer_cost_cannot_bypass_server_validation_and_modal_stays_open(): void
    {
        $user = User::factory()->create(['role' => UserRole::COORDINATOR]);
        [$from, $to] = $this->accountsAndRates();
        $component = $this->actingAs($user)->capitalTransferComponent($from, $to)
            ->set('transfer_cost', 'not-a-number')
            ->call('createTransfer')
            ->assertHasErrors(['transfer_cost' => 'numeric'])
            ->assertSet('showTransferForm', true);

        $component->set('transfer_cost', '-1')
            ->call('createTransfer')
            ->assertHasErrors(['transfer_cost' => 'min'])
            ->assertSet('showTransferForm', true);

        $this->assertDatabaseCount('capital_transfers', 0);
    }

    private function capitalTransferComponent(CapitalAccount $from, CapitalAccount $to)
    {
        return Livewire::test('capital-transfers.index')
            ->call('openTransferForm')
            ->set('from_account_id', $from->id)
            ->set('to_account_id', $to->id)
            ->set('source_amount', '50');
    }

    /** @return array{CapitalAccount, CapitalAccount} */
    private function accountsAndRates(): array
    {
        $from = $this->account(Branch::UAE, CurrencyType::USD, CapitalAccountType::CAPITAL, '200.00');
        $to = $this->account(Branch::GAZA, CurrencyType::ILS, CapitalAccountType::CAPITAL, '0.00');
        $this->account(Branch::GAZA, CurrencyType::ILS, CapitalAccountType::PROFIT, '100.00');

        ExchangeRate::query()->create(['currency' => CurrencyType::USD, 'rate_to_usd' => '1.00000000']);
        ExchangeRate::query()->create(['currency' => CurrencyType::ILS, 'rate_to_usd' => '0.26954100']);

        return [$from, $to];
    }

    private function account(Branch $branch, CurrencyType $currency, CapitalAccountType $type, string $balance): CapitalAccount
    {
        return CapitalAccount::query()->create([
            'name' => strtoupper($branch->value.' '.$currency->value.' '.$type->value),
            'branch' => $branch,
            'currency' => $currency,
            'account_type' => $type,
            'balance' => $balance,
            'is_active' => true,
        ]);
    }
}
