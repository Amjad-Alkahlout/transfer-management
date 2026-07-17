<?php

use App\Enums\Branch;
use App\Enums\CapitalAccountType;
use App\Enums\CurrencyType;
use App\Enums\PaymentStatus;
use App\Enums\TransferStatus;
use App\Models\CapitalAccount;
use App\Models\CommissionRule;
use App\Models\ExchangeRate;
use App\Models\Transfer;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')]
class extends Component {

    #[Computed]
    public function financialOverview()
    {
        return [
            'gaza_usd' => CapitalAccount::query()
                    ->where('branch', Branch::GAZA)
                    ->where('account_type', CapitalAccountType::CAPITAL)
                    ->where('currency', CurrencyType::USD)
                    ->value('balance') ?? 0,

            'uae_usd' => CapitalAccount::query()
                    ->where('branch', Branch::UAE)
                    ->where('account_type', CapitalAccountType::CAPITAL)
                    ->where('currency', CurrencyType::USD)
                    ->value('balance') ?? 0,

            'uae_aed' => CapitalAccount::query()
                    ->where('branch', Branch::UAE)
                    ->where('account_type', CapitalAccountType::CAPITAL)
                    ->where('currency', CurrencyType::AED)
                    ->value('balance') ?? 0,

            'profit' => CapitalAccount::query()
                    ->where('branch', Branch::GAZA)
                    ->where('account_type', CapitalAccountType::PROFIT)
                    ->where('currency', CurrencyType::USD)
                    ->value('balance') ?? 0,
        ];
    }

    #[Computed]
    public function transferStats()
    {
        return [
            'pending' => Transfer::where('status', TransferStatus::PENDING)->count(),
            'completed' => Transfer::where('status', TransferStatus::COMPLETED)->count(),
            'cancelled' => Transfer::where('status', TransferStatus::CANCELLED)->count(),
        ];
    }

    #[Computed]
    public function paymentStats()
    {
        return [
            'unpaid' => Transfer::where('payment_status', PaymentStatus::UNPAID)->count(),
            'partially_paid' => Transfer::where('payment_status', PaymentStatus::PARTIALLY_PAID)->count(),
            'paid' => Transfer::where('payment_status', PaymentStatus::PAID)->count(),
        ];
    }

    #[Computed]
    public function exchangeRates()
    {
        return ExchangeRate::orderBy('currency')->get();
    }

    #[Computed]
    public function commissionStats()
    {
        return CommissionRule::selectRaw('currency, COUNT(*) as total')
            ->groupBy('currency')
            ->get();
    }
};
?>

<div>

    <x-ui.page-header
        title="Dashboard"
        description="Money Transfer System Overview"
    >
        <x-slot:actions>
            <x-ui.button :href="route('transfers.create')">
                Create Transfer
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-8">

        <div class="mb-5">
            <h2 class="text-xl font-semibold text-gray-900">
                Financial Overview
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Current balances across company accounts.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">

            <x-ui.stat-card
                title="Gaza Capital"
                :value="'$'.number_format($this->financialOverview['gaza_usd'],2)"
                subtitle="USD"
                color="blue"
            >
                <x-slot:icon>
                    <x-heroicon-o-banknotes class="h-7 w-7 text-blue-600"/>
                </x-slot:icon>
            </x-ui.stat-card>

            <x-ui.stat-card
                title="UAE Capital (USD)"
                :value="'$'.number_format($this->financialOverview['uae_usd'],2)"
                subtitle="USD"
                color="green"
            >
                <x-slot:icon>
                    <x-heroicon-o-currency-dollar class="h-7 w-7 text-green-600"/>
                </x-slot:icon>
            </x-ui.stat-card>

            <x-ui.stat-card
                title="UAE Capital (AED)"
                :value="'AED '.number_format($this->financialOverview['uae_aed'],2)"
                subtitle="AED"
                color="orange"
            >
                <x-slot:icon>
                    <x-heroicon-o-building-library class="h-7 w-7 text-orange-600"/>
                </x-slot:icon>
            </x-ui.stat-card>

            <x-ui.stat-card
                title="Profit"
                :value="'$'.number_format($this->financialOverview['profit'],2)"
                subtitle="USD"
                color="emerald"
            >
                <x-slot:icon>
                    <x-heroicon-o-chart-bar class="h-7 w-7 text-emerald-600"/>
                </x-slot:icon>
            </x-ui.stat-card>

        </div>

    </div>

    <div class="mb-8">

        <x-ui.card title="Quick Actions">

            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">

                <x-ui.button
                    class="justify-center py-4"
                    :href="route('transfers.index')"
                    variant="secondary"
                >
                    Transfers
                </x-ui.button>

                <x-ui.button
                    class="justify-center py-4"
                    :href="route('capital-transfers.index')"
                    variant="secondary"
                >
                    Capital Transfers
                </x-ui.button>

                <x-ui.button
                    class="justify-center py-4"
                    :href="route('capital-accounts.index')"
                    variant="secondary"
                >
                    Capital Accounts
                </x-ui.button>

                <x-ui.button
                    class="justify-center py-4"
                    :href="route('exchange-rates.index')"
                    variant="secondary"
                >
                    Exchange Rates
                </x-ui.button>

                <x-ui.button
                    class="justify-center py-4"
                    :href="route('commission-rules.index')"
                    variant="secondary"
                >
                    Commission Rules
                </x-ui.button>

            </div>

        </x-ui.card>

    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

        <x-ui.card
            title="Transfer Overview"
            description="Current transfer status summary."
        >

            <x-ui.table>

                <x-ui.table-header>

                    <x-ui.table-head>Status</x-ui.table-head>

                    <x-ui.table-head>Total</x-ui.table-head>

                </x-ui.table-header>

                <x-ui.table-body>

                    <x-ui.table-row>

                        <x-ui.table-cell>
                            <x-ui.badge variant="yellow">
                                Pending
                            </x-ui.badge>
                        </x-ui.table-cell>

                        <x-ui.table-cell>
                            {{ $this->transferStats['pending'] }}
                        </x-ui.table-cell>

                    </x-ui.table-row>

                    <x-ui.table-row>

                        <x-ui.table-cell>
                            <x-ui.badge variant="green">
                                Completed
                            </x-ui.badge>
                        </x-ui.table-cell>

                        <x-ui.table-cell>
                            {{ $this->transferStats['completed'] }}
                        </x-ui.table-cell>

                    </x-ui.table-row>

                    <x-ui.table-row>

                        <x-ui.table-cell>
                            <x-ui.badge variant="red">
                                Cancelled
                            </x-ui.badge>
                        </x-ui.table-cell>

                        <x-ui.table-cell>
                            {{ $this->transferStats['cancelled'] }}
                        </x-ui.table-cell>

                    </x-ui.table-row>

                </x-ui.table-body>

            </x-ui.table>

        </x-ui.card>

        <x-ui.card
            title="Payment Overview"
            description="Current payment status summary."
        >

            <x-ui.table>

                <x-ui.table-header>

                    <x-ui.table-head>Status</x-ui.table-head>
                    <x-ui.table-head>Total</x-ui.table-head>

                </x-ui.table-header>

                <x-ui.table-body>

                    <x-ui.table-row>

                        <x-ui.table-cell>
                            <x-ui.badge variant="red">
                                Unpaid
                            </x-ui.badge>
                        </x-ui.table-cell>

                        <x-ui.table-cell>
                            {{ $this->paymentStats['unpaid'] }}
                        </x-ui.table-cell>

                    </x-ui.table-row>

                    <x-ui.table-row>

                        <x-ui.table-cell>
                            <x-ui.badge variant="orange">
                                Partially Paid
                            </x-ui.badge>
                        </x-ui.table-cell>

                        <x-ui.table-cell>
                            {{ $this->paymentStats['partially_paid'] }}
                        </x-ui.table-cell>

                    </x-ui.table-row>

                    <x-ui.table-row>

                        <x-ui.table-cell>
                            <x-ui.badge variant="green">
                                Paid
                            </x-ui.badge>
                        </x-ui.table-cell>

                        <x-ui.table-cell>
                            {{ $this->paymentStats['paid'] }}
                        </x-ui.table-cell>

                    </x-ui.table-row>

                </x-ui.table-body>

            </x-ui.table>

        </x-ui.card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">

        <x-ui.card
            title="Exchange Rates"
            description="Current exchange rates."
        >

            <x-ui.table>

                <x-ui.table-header>

                    <x-ui.table-head>
                        Currency
                    </x-ui.table-head>

                    <x-ui.table-head>
                        Rate to USD
                    </x-ui.table-head>

                </x-ui.table-header>

                <x-ui.table-body>

                    @foreach($this->exchangeRates as $rate)

                        <x-ui.table-row>

                            <x-ui.table-cell>
                                {{ strtoupper($rate->currency->value) }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                {{ number_format($rate->rate_to_usd, 4) }}
                            </x-ui.table-cell>

                        </x-ui.table-row>

                    @endforeach

                </x-ui.table-body>

            </x-ui.table>

        </x-ui.card>

        <x-ui.card
            title="Commission Rules"
            description="Configured commission rules."
        >

            <x-ui.table>

                <x-ui.table-header>

                    <x-ui.table-head>
                        Currency
                    </x-ui.table-head>

                    <x-ui.table-head>
                        Total Rules
                    </x-ui.table-head>

                </x-ui.table-header>

                <x-ui.table-body>

                    @foreach($this->commissionStats as $rule)

                        <x-ui.table-row>

                            <x-ui.table-cell>
                                {{ strtoupper($rule->currency->value) }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                {{ $rule->total }}
                            </x-ui.table-cell>

                        </x-ui.table-row>

                    @endforeach

                </x-ui.table-body>

            </x-ui.table>

        </x-ui.card>
    </div>

</div>


