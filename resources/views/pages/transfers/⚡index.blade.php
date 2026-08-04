<?php

use App\Enums\ReceiverMethod;
use App\Enums\TransferStatus;
use App\Models\Transfer;
use App\Support\LocalTime;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Enums\PaymentStatus;
use Livewire\Attributes\Layout;

new #[Layout('layouts::app')]
class extends Component {

    public ?string $status = null;
    public ?string $paymentStatus = null;

    public function mount()
    {
        $this->status = request('status');
        $this->paymentStatus = request('payment_status');
    }

    #[Computed]
    public function transfers()
    {
        $query = Transfer::query()
            ->with('creator');

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->paymentStatus) {
            $query->where('payment_status', $this->paymentStatus)->where('status', '!=', TransferStatus::CANCELLED);
        }

        return $query
            ->latest()
            ->paginate(15);
    }

    public function goToTransfer(Transfer $transfer)
    {
        return redirect()->route('transfers.show', $transfer);
    }
};
?>

<div>
    <x-ui.page-header
        :title="__('transfers.page.index_title')"

        :description="__('transfers.page.index_description')"
    >
        <x-slot:actions>
            @can('create-transfer')
                <x-ui.button
                    :href="route('transfers.create')"
                >
                    {{ __('transfers.buttons.create') }}
                </x-ui.button>
            @endcan

        </x-slot:actions>

    </x-ui.page-header>

    <x-ui.card class="mb-6">

        <x-ui.button
            :href="route('dashboard')"
            variant="secondary"
        >
            {{ app()->getLocale() === 'ar' ? '→' : '←' }}
            {{ __('capital_accounts.buttons.back') }}
        </x-ui.button>

    </x-ui.card>

    @if($status || $paymentStatus)

        <div class="mb-6 flex items-center justify-between">

            <x-ui.alert color="blue">

                {{ __('transfers.filters.showing') }}

                @if($status)
                    {{ str($status)->replace('_', ' ')->title() }} {{ __('transfers.filters.transfers') }}
                @endif

                @if($paymentStatus)
                    {{ str($paymentStatus)->replace('_', ' ')->title() }} {{ __('transfers.filters.payments') }}
                @endif

            </x-ui.alert>

            <x-ui.button
                :href="route('transfers.index')"
                variant="secondary"
            >
                {{ __('transfers.filters.clear') }}
            </x-ui.button>

        </div>

    @endif

    <x-ui.card
        :title="__('transfers.table.title')"

        :description="__('transfers.table.description')"
    >

        <x-ui.table>

            <x-ui.table-header>

                <x-ui.table-head>{{ __('transfers.table.reference') }}</x-ui.table-head>

                <x-ui.table-head>{{ __('transfers.table.receiver') }}</x-ui.table-head>

                <x-ui.table-head>{{ __('transfers.table.method') }}</x-ui.table-head>


                <x-ui.table-head>{{ __('transfers.table.receiver_gets') }}</x-ui.table-head>

                <x-ui.table-head>{{ __('transfers.table.customer_pays') }}</x-ui.table-head>

                <x-ui.table-head>{{ __('transfers.table.commission') }}</x-ui.table-head>

                <x-ui.table-head>{{ __('transfers.table.transfer_status') }}</x-ui.table-head>

                <x-ui.table-head>{{ __('transfers.table.payment_status') }}</x-ui.table-head>

                <x-ui.table-head>{{ __('transfers.table.created_by') }}</x-ui.table-head>

                <x-ui.table-head>{{ __('transfers.table.created') }}</x-ui.table-head>


            </x-ui.table-header>

            <x-ui.table-body>
                @if($this->transfers->isEmpty())

                    <x-ui.table-row>

                        <x-ui.table-cell colspan="12" class="p-0">

                            <x-ui.empty-state
                                :title="__('transfers.empty_state.title')"

                                :description="__('transfers.empty_state.description')"
                            >

                                <x-slot:actions>
                                    @can('create-transfer')
                                        <x-ui.button :href="route('transfers.create')">
                                            {{ __('transfers.buttons.create') }}
                                        </x-ui.button>
                                    @endcan

                                </x-slot:actions>

                            </x-ui.empty-state>

                        </x-ui.table-cell>

                    </x-ui.table-row>

                @else

                    @foreach($this->transfers as $transfer)
                        <x-ui.table-row
                            wire:click="goToTransfer({{ $transfer->id }})"
                            class="cursor-pointer hover:bg-gray-50 transition"
                        >

                            <x-ui.table-cell>
                                {{ $transfer->reference_number }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                {{ $transfer->receiver_name }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                @if($transfer->receiver_method === ReceiverMethod::BANK)
                                    {{ $transfer->receiver_method->label() }}
                                    <br>
                                    {{ $transfer->receiver_account_number }}
                                @else
                                    {{ str($transfer->receiver_method->value)->replace('_',' ')->title() }}
                                    <br>
                                    {{ $transfer->receiver_wallet_phone }}
                                @endif
                            </x-ui.table-cell>


                            <x-ui.table-cell>
                                {{ number_format($transfer->transfer_amount,2) }} {{ $transfer->requested_currency->symbol() }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                {{ number_format($transfer->customer_payable_amount,2) }} {{ $transfer->customer_payable_currency->symbol() }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                {{ number_format($transfer->commission_amount,2) }} {{ $transfer->commission_currency->symbol() }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>

                                @switch($transfer->status)

                                    @case(TransferStatus::PENDING)

                                        <x-ui.badge color="yellow">
                                            {{ TransferStatus::PENDING->label() }}
                                        </x-ui.badge>

                                        @break

                                    @case(TransferStatus::COMPLETED)

                                        <x-ui.badge color="green">
                                            {{ TransferStatus::COMPLETED->label() }}
                                        </x-ui.badge>

                                        @break

                                    @case(TransferStatus::CANCELLED)

                                        <x-ui.badge color="red">
                                            {{ TransferStatus::CANCELLED->label() }}
                                        </x-ui.badge>

                                        @break

                                @endswitch

                            </x-ui.table-cell>

                            <x-ui.table-cell>

                                @switch($transfer->payment_status)

                                    @case(PaymentStatus::UNPAID)

                                        <x-ui.badge color="red">
                                            {{ PaymentStatus::UNPAID->label() }}
                                        </x-ui.badge>

                                        @break

                                    @case(PaymentStatus::PARTIALLY_PAID)

                                        <x-ui.badge color="orange">
                                            {{ PaymentStatus::PARTIALLY_PAID->label() }}
                                        </x-ui.badge>

                                        @break

                                    @case(PaymentStatus::PAID)

                                        <x-ui.badge color="green">
                                            {{ PaymentStatus::PAID->label() }}
                                        </x-ui.badge>

                                        @break

                                @endswitch

                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                {{ $transfer->creator?->name }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                {{ LocalTime::format($transfer->created_at) }}
                            </x-ui.table-cell>


                        </x-ui.table-row>
                    @endforeach
                @endif

            </x-ui.table-body>

        </x-ui.table>


        <x-slot:footer>

            {{ $this->transfers->links() }}

        </x-slot:footer>

    </x-ui.card>

</div>
