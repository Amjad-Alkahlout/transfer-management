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
use App\Services\TelegramLinkService;


new #[Layout('layouts::app')]
class extends Component {

    public bool $showTelegramModal = false;
    public ?string $telegramLinkCode = null;
    public bool $waitingForTelegramLink = false;

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

            'outstanding_receivables' => Transfer::query()
                ->where('status', '!=', TransferStatus::CANCELLED)
                ->sum('remaining_amount'),
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
    public function openTransfers(?string $status = null, ?string $paymentStatus = null)
    {
        return redirect()->route('transfers.index', [
            'status' => $status,
            'payment_status' => $paymentStatus,
        ]);
    }

    public function openTelegramModal(
        TelegramLinkService $telegramLinkService
    ): void {

        if (auth()->user()->telegram_chat_id) {

            $this->waitingForTelegramLink = false;

        } else {

            $this->telegramLinkCode = $telegramLinkService
                ->generateLinkCode(auth()->user());

            $this->waitingForTelegramLink = true;
        }

        $this->showTelegramModal = true;
    }

    public function disconnectTelegram(
        TelegramLinkService $telegramLinkService
    ): void
    {
        $telegramLinkService->unlink(auth()->user());

        $this->showTelegramModal = false;

        session()->flash(
            'success',
            __('dashboard.messages.telegram_disconnected')
        );
    }

    public function relinkTelegram(
        TelegramLinkService $telegramLinkService
    ): void
    {
        $telegramLinkService->unlink(auth()->user());

        $this->telegramLinkCode =
            $telegramLinkService
                ->generateLinkCode(auth()->user());
    }

    public function closeTelegramModal(): void
    {
        $this->showTelegramModal = false;
        $this->waitingForTelegramLink = false;
    }

    public function checkTelegramLink(): void
    {
        if (! $this->waitingForTelegramLink) {
            return;
        }

        $user = auth()->user()->fresh();

        if (! $user->telegram_chat_id) {
            return;
        }

        $this->waitingForTelegramLink = false;
        $this->showTelegramModal = false;

        session()->flash(
            'success',
            __('dashboard.messages.telegram_linked')
        );
    }
};
?>

<div>

    <x-ui.page-header
        :title="__('dashboard.page.title')"
        :description="__('dashboard.page.description')"
    >

        <x-slot:actions>
            @can('create-transfer')
                <x-ui.button :href="route('transfers.create')">
                    {{ __('dashboard.quick_actions.create_transfer') }}
                </x-ui.button>
            @endcan

            <x-ui.button wire:click="openTelegramModal">

                @if(auth()->user()->telegram_chat_id)

                    🟢 {{ __('dashboard.telegram.connected') }}

                @else

                    🔗 {{ __('dashboard.telegram.link') }}

                @endif

            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>


    @can('view-financial-dashboard')

    <div class="mb-8">

        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">
                {{ __('dashboard.financial.title') }}
            </h2>

            <p class="mt-1 text-base text-gray-500">
                {{ __('dashboard.financial.description') }}
            </p>
        </div>

        <div class="grid gap-5 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">

            <x-ui.stat-card
                :title="__('dashboard.financial.gaza_capital')"
                :value="'$'.number_format($this->financialOverview['gaza_usd'],2)"
                :subtitle="__('dashboard.financial.current_balance')"
                color="gray"
            >
                <x-slot:icon>
                    <x-heroicon-o-building-office-2 class="h-7 w-7 text-gray-600"/>
                </x-slot:icon>
            </x-ui.stat-card>

            <x-ui.stat-card
                :title="__('dashboard.financial.uae_capital_usd')"
                :value="'$'.number_format($this->financialOverview['uae_usd'],2)"
                :subtitle="__('dashboard.financial.current_balance')"
                color="gray"
            >
                <x-slot:icon>
                    <x-heroicon-o-currency-dollar class="h-7 w-7 text-gray-600"/>
                </x-slot:icon>
            </x-ui.stat-card>

            <x-ui.stat-card
                :title="__('dashboard.financial.uae_capital_aed')"
                :value="number_format($this->financialOverview['uae_aed'],2)"
                :subtitle="__('dashboard.financial.current_balance')"
                color="gray"
            >
                <x-slot:icon>
                    <x-heroicon-o-building-library class="h-7 w-7 text-gray-600"/>
                </x-slot:icon>
            </x-ui.stat-card>


            <x-ui.stat-card
                :title="__('dashboard.financial.profit')"
                :value="'$'.number_format($this->financialOverview['profit'],2)"
                :subtitle="__('dashboard.financial.net_profit')"
                color="green"
            >
                <x-slot:icon>
                    <x-heroicon-o-chart-bar class="h-7 w-7 text-emerald-600"/>
                </x-slot:icon>
            </x-ui.stat-card>


            <x-ui.stat-card
                :title="__('dashboard.financial.customer_receivables')"
                :value="number_format($this->financialOverview['outstanding_receivables'],2)"
                :subtitle="__('dashboard.financial.outstanding_amount')"
                color="red"
            >
                <x-slot:icon>
                    <x-heroicon-o-clock class="h-7 w-7 text-red-600"/>
                </x-slot:icon>
            </x-ui.stat-card>


        </div>

    </div>
    @endcan


    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">


        <x-ui.card
            :title="__('dashboard.transfers.title')"
            :description="__('dashboard.transfers.description')"
        >

            <x-ui.table>

                <x-ui.table-header>

                    <x-ui.table-head>{{ __('dashboard.transfers.status') }}</x-ui.table-head>

                    <x-ui.table-head>{{ __('dashboard.transfers.total') }}</x-ui.table-head>

                </x-ui.table-header>

                <x-ui.table-body>

                    <x-ui.table-row
                        wire:click="openTransfers('pending')"
                        class="cursor-pointer">

                        <x-ui.table-cell>
                            <x-ui.badge color="yellow">
                                {{ \App\Enums\TransferStatus::PENDING->label() }}
                            </x-ui.badge>
                        </x-ui.table-cell>

                        <x-ui.table-cell>
                            {{ $this->transferStats['pending'] }}
                        </x-ui.table-cell>

                    </x-ui.table-row>

                    <x-ui.table-row wire:click="openTransfers('completed')"
                                    class="cursor-pointer">

                        <x-ui.table-cell>
                            <x-ui.badge color="green">
                                {{ \App\Enums\TransferStatus::COMPLETED->label() }}
                            </x-ui.badge>
                        </x-ui.table-cell>

                        <x-ui.table-cell>
                            {{ $this->transferStats['completed'] }}
                        </x-ui.table-cell>

                    </x-ui.table-row>

                    <x-ui.table-row wire:click="openTransfers('cancelled')"
                                    class="cursor-pointer">

                        <x-ui.table-cell>
                            <x-ui.badge color="red">
                                {{ \App\Enums\TransferStatus::CANCELLED->label() }}
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
            :title="__('dashboard.payments.title')"

            :description="__('dashboard.payments.description')"
        >

            <x-ui.table>

                <x-ui.table-header>

                    <x-ui.table-head>{{ __('dashboard.payments.status') }}</x-ui.table-head>
                    <x-ui.table-head>{{ __('dashboard.payments.total') }}</x-ui.table-head>

                </x-ui.table-header>

                <x-ui.table-body>

                    <x-ui.table-row wire:click="openTransfers(null, 'unpaid')"
                                    class="cursor-pointer">

                        <x-ui.table-cell>
                            <x-ui.badge color="red">
                                {{ \App\Enums\PaymentStatus::UNPAID->label() }}
                            </x-ui.badge>
                        </x-ui.table-cell>

                        <x-ui.table-cell>
                            {{ $this->paymentStats['unpaid'] }}
                        </x-ui.table-cell>

                    </x-ui.table-row>

                    <x-ui.table-row wire:click="openTransfers(null, 'partially_paid')"
                                    class="cursor-pointer">

                        <x-ui.table-cell>
                            <x-ui.badge color="orange">
                                {{ \App\Enums\PaymentStatus::PARTIALLY_PAID->label() }}
                            </x-ui.badge>
                        </x-ui.table-cell>

                        <x-ui.table-cell>
                            {{ $this->paymentStats['partially_paid'] }}
                        </x-ui.table-cell>

                    </x-ui.table-row>

                    <x-ui.table-row wire:click="openTransfers(null, 'paid')"
                                    class="cursor-pointer">

                        <x-ui.table-cell>
                            <x-ui.badge color="green">
                                {{ \App\Enums\PaymentStatus::PAID->label() }}
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
            :title="__('dashboard.exchange_rates.title')"
            :description="__('dashboard.exchange_rates.description')"
        >

            <x-ui.table>

                <x-ui.table-header>

                    <x-ui.table-head>
                        {{ __('dashboard.exchange_rates.currency') }}
                    </x-ui.table-head>

                    <x-ui.table-head>
                        {{ __('dashboard.exchange_rates.rate_to_usd') }}
                    </x-ui.table-head>

                </x-ui.table-header>

                <x-ui.table-body>

                    @foreach($this->exchangeRates as $rate)

                        <x-ui.table-row>

                            <x-ui.table-cell>
                                {{ $rate->currency->label() }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                {{ number_format($rate->rate_to_usd, 4) }}
                            </x-ui.table-cell>

                        </x-ui.table-row>

                    @endforeach

                </x-ui.table-body>

            </x-ui.table>

        </x-ui.card>


    </div>

    <x-ui.modal
        :show="$showTelegramModal"
        :title="__('dashboard.telegram.modal_title')"
        maxWidth="lg"
    >
        <div
            @if($waitingForTelegramLink)
                wire:poll.2s="checkTelegramLink"
            @endif
        >

        @if(auth()->user()->telegram_chat_id)

            <div class="space-y-6">

                <div class="text-center">

                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="h-8 w-8 text-green-600"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="2">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M5 13l4 4L19 7"/>

                        </svg>

                    </div>

                    <h3 class="mt-4 text-xl font-semibold">
                        {{ __('dashboard.telegram.connected_title') }}
                    </h3>

                    <p class="mt-2 text-sm text-gray-500">
                        {{ __('dashboard.telegram.connected_description') }}
                    </p>

                </div>

                <div class="rounded-xl border bg-gray-50 p-4">

                    <div class="flex items-center justify-between">

                    <span class="text-gray-600">
                        {{ __('dashboard.telegram.status') }}
                    </span>

                        <span class="rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-700">
                        {{ __('dashboard.telegram.enabled') }}
                    </span>

                    </div>

                </div>

            </div>

            <x-slot:footer>

                <div class="flex justify-between">

                    <x-ui.button
                        variant="danger"
                        wire:click="disconnectTelegram"
                    >
                        {{ __('dashboard.telegram.disconnect') }}
                    </x-ui.button>

                    <div class="flex gap-3">

                        <x-ui.button
                            wire:click="relinkTelegram"
                        >
                            {{ __('dashboard.telegram.relink') }}
                        </x-ui.button>

                        <x-ui.button
                            variant="secondary"
                            wire:click="closeTelegramModal"
                        >
                            {{ __('dashboard.telegram.close') }}
                        </x-ui.button>

                    </div>

                </div>

            </x-slot:footer>

        @else

            <div class="space-y-6">

                <div class="text-center">

                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-sky-100">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="h-8 w-8 text-sky-600"
                             fill="currentColor"
                             viewBox="0 0 24 24">

                            <path d="M9.993 15.674 9.87 19.2c.41 0 .588-.176.802-.388l1.924-1.84 3.988 2.92c.732.404 1.25.192 1.448-.68l2.624-12.304c.234-1.09-.394-1.517-1.106-1.252L3.73 11.27c-1.066.416-1.05 1.01-.181 1.28l4.54 1.415L18.63 7.32c.496-.328.948-.146.576.182"/>

                        </svg>

                    </div>

                    <h3 class="mt-4 text-xl font-semibold">
                        {{ __('dashboard.telegram.link_title') }}
                    </h3>

                    <p class="mt-2 text-sm text-gray-500">
                        {{ __('dashboard.telegram.link_description') }}
                    </p>

                </div>

                <div class="rounded-xl border bg-gray-50 p-5">

                    <div class="flex items-center justify-between">

                        <div>

                            <h4 class="font-medium">
                                {{ __('dashboard.telegram.step_1') }}
                            </h4>

                            <p class="mt-1 text-sm text-gray-500">
                                {{ __('dashboard.telegram.open_bot_description') }}
                            </p>

                        </div>

                        <a
                            href="https://t.me/Money_transfer_management_bot"
                            target="_blank"
                            class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700"
                        >
                            {{ __('dashboard.telegram.open_bot') }}
                        </a>

                    </div>

                </div>

                <div class="rounded-xl border bg-gray-50 p-5">

                    <h4 class="font-medium">
                        {{ __('dashboard.telegram.step_2') }}
                    </h4>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ __('dashboard.telegram.send_command') }}
                    </p>

                    <div
                        x-data="{ command: '/link {{ $telegramLinkCode }}' }"
                        class="mt-4"
                    >

                        <div
                            class="rounded-lg border bg-white px-4 py-3 text-center font-mono text-lg"
                        >
                            /link {{ $telegramLinkCode }}
                        </div>

                        <button
                            type="button"
                            x-on:click="
                            navigator.clipboard.writeText(command)
                        "
                            class="mt-4 w-full rounded-lg border px-4 py-2 font-medium hover:bg-gray-100"
                        >
                            📋 {{ __('dashboard.telegram.copy_command') }}
                        </button>

                    </div>

                </div>

                <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700">

                    ⏳ {{ __('dashboard.telegram.code_expire') }}

                </div>

            </div>

            <x-slot:footer>

                <div class="flex justify-end">

                    <x-ui.button
                        variant="secondary"
                        wire:click="closeTelegramModal"
                    >
                        {{ __('dashboard.telegram.close') }}
                    </x-ui.button>

                </div>

            </x-slot:footer>

        @endif
        </div>
    </x-ui.modal>

</div>


