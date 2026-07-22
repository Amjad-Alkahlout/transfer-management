<?php

use App\Enums\ReceiverMethod;
use App\Enums\TransferStatus;
use App\Models\Transfer;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Enums\PaymentStatus;
use Livewire\Attributes\Layout;

new #[Layout('layouts::app')] class extends Component {

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
            $query->where('payment_status', $this->paymentStatus);
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
        title="Transfers"
        description="Manage customer transfers."
    >
        <x-slot:actions>
            @can('create-transfer')
            <x-ui.button
                :href="route('transfers.create')"
            >
                Create Transfer
            </x-ui.button>
            @endcan

        </x-slot:actions>

    </x-ui.page-header>

    <x-ui.card class="mb-6">

        <x-ui.button
            :href="route('dashboard')"
            variant="secondary"
        >
            ← Dashboard
        </x-ui.button>

    </x-ui.card>

    @if($status || $paymentStatus)

        <div class="mb-6 flex items-center justify-between">

            <x-ui.alert color="blue">

                Showing:

                @if($status)
                    {{ str($status)->replace('_', ' ')->title() }} Transfers
                @endif

                @if($paymentStatus)
                    {{ str($paymentStatus)->replace('_', ' ')->title() }} Payments
                @endif

            </x-ui.alert>

            <x-ui.button
                :href="route('transfers.index')"
                variant="secondary"
            >
                Clear Filter
            </x-ui.button>

        </div>

    @endif

    <x-ui.card
        title="Transfers"
        description="All customer transfers."
    >

        <x-ui.table>

            <x-ui.table-header>

                <x-ui.table-head>Reference</x-ui.table-head>

                <x-ui.table-head>Receiver</x-ui.table-head>

                <x-ui.table-head>Method</x-ui.table-head>


                <x-ui.table-head>Receiver Gets</x-ui.table-head>

                <x-ui.table-head>Customer Pays</x-ui.table-head>

                <x-ui.table-head>Commission</x-ui.table-head>

                <x-ui.table-head>Transfer Status</x-ui.table-head>

                <x-ui.table-head>Payment Status</x-ui.table-head>

                <x-ui.table-head>Created By</x-ui.table-head>

                <x-ui.table-head>Created</x-ui.table-head>


            </x-ui.table-header>

            <x-ui.table-body>
                @if($this->transfers->isEmpty())

                    <x-ui.table-row>

                        <x-ui.table-cell colspan="12" class="p-0">

                            <x-ui.empty-state
                                title="No transfers found"
                                description="Create your first transfer."
                            >

                                <x-slot:actions>
                                    @can('create-transfer')
                                    <x-ui.button :href="route('transfers.create')">
                                        Create Transfer
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
                                {{ str($transfer->receiver_method->value)->replace('_',' ')->title() }}
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
                                        Pending
                                    </x-ui.badge>

                                    @break

                                @case(TransferStatus::COMPLETED)

                                    <x-ui.badge color="green">
                                        Completed
                                    </x-ui.badge>

                                    @break

                                @case(TransferStatus::CANCELLED)

                                    <x-ui.badge color="red">
                                        Cancelled
                                    </x-ui.badge>

                                    @break

                            @endswitch

                        </x-ui.table-cell>

                        <x-ui.table-cell>

                            @switch($transfer->payment_status)

                                @case(PaymentStatus::UNPAID)

                                    <x-ui.badge color="red">
                                        Unpaid
                                    </x-ui.badge>

                                    @break

                                @case(PaymentStatus::PARTIALLY_PAID)

                                    <x-ui.badge color="orange">
                                        Partially Paid
                                    </x-ui.badge>

                                    @break

                                @case(PaymentStatus::PAID)

                                    <x-ui.badge color="green">
                                        Paid
                                    </x-ui.badge>

                                    @break

                            @endswitch

                        </x-ui.table-cell>

                        <x-ui.table-cell>
                            {{ $transfer->creator?->name }}
                        </x-ui.table-cell>

                        <x-ui.table-cell>
                            {{ $transfer->created_at->format('d/m/Y H:i') }}
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
