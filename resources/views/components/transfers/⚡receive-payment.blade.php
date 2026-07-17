<?php

use App\Enums\PaymentStatus;
use App\Enums\TransferStatus;
use App\Models\Transfer;
use App\Services\ReceivePaymentService;
use Livewire\Attributes\Computed;
use Livewire\Component;


new class extends Component {
    public Transfer $transfer;

    public $payment_amount;
    public $notes;

    public function mount(Transfer $transfer)
    {
        $this->transfer = $transfer;
    }

    #[Computed]
    public function payments()
    {
        return $this->transfer->payments()->latest()->get();
    }


    public function receivePayment()
    {

        $this->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ]);


        try {

            app(ReceivePaymentService::class)->receive(
                $this->transfer,
                $this->payment_amount,
                $this->notes,
            );

        } catch (\Throwable $e) {

            $this->addError('payment_amount', $e->getMessage());

            return;
        }

        $this->reset([
            'payment_amount',
            'notes',
        ]);
        session()->flash('message', 'Payment received successfully.');
        return redirect()->route('transfers.show', $this->transfer);
    }
};
?>

<div>

    <x-ui.page-header
        title="Receive Payment"
        :description="$transfer->reference_number"
    >
        <x-slot:actions>

            <x-ui.button
                :href="route('transfers.show', $transfer)"
                variant="secondary"
            >
                ← Back
            </x-ui.button>

        </x-slot:actions>
    </x-ui.page-header>

    @if(session()->has('message'))

        <x-ui.alert color="success">
            {{ session('message') }}
        </x-ui.alert>

    @endif

    <x-ui.card
        title="Payment Summary"
        description="Current payment status for this transfer."
    >

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

            <div>
                <div class="text-sm text-gray-500">
                    Requested Amount
                </div>

                <div class="text-lg font-semibold">
                    {{ $transfer->customer_payable_currency->symbol() }}
                    {{ number_format($transfer->customer_payable_amount,2) }}
                </div>
            </div>

            <div>
                <div class="text-sm text-gray-500">
                    Paid
                </div>

                <div class="text-lg font-semibold text-green-600">
                    {{ $transfer->customer_payable_currency->symbol() }}
                    {{ number_format($transfer->paid_amount,2) }}
                </div>
            </div>

            <div>
                <div class="text-sm text-gray-500">
                    Remaining
                </div>

                <div class="text-lg font-semibold text-red-600">
                    {{ $transfer->customer_payable_currency->symbol() }}
                    {{ number_format($transfer->remaining_amount,2) }}
                </div>
            </div>

        </div>

    </x-ui.card>

    <x-ui.form-section
        title="Receive Payment"
        description="Record a customer payment."
        class="grid-cols-1"
    >

        <form
            wire:submit.prevent="receivePayment"
            class="space-y-5"
        >

            <x-ui.input
                label="Payment Amount"
                name="payment_amount"
                type="number"
                step="0.01"
                wire:model="payment_amount"
            />

            <x-ui.textarea
                label="Notes"
                name="notes"
                rows="3"
                wire:model="notes"
            />

            <div class="flex justify-end gap-3">

                <x-ui.button
                    :href="route('transfers.show',$transfer)"
                    variant="secondary"
                >
                    Cancel
                </x-ui.button>

                <x-ui.button type="submit">
                    Receive Payment
                </x-ui.button>

            </div>

        </form>

    </x-ui.form-section>

    <x-ui.card
        title="Payment History"
        description="All recorded payments for this transfer."
    >

        <x-ui.table>

            <x-ui.table-header>

                <x-ui.table-head>
                    Amount
                </x-ui.table-head>

                <x-ui.table-head>
                    Received By
                </x-ui.table-head>

                <x-ui.table-head>
                    Date
                </x-ui.table-head>

                <x-ui.table-head>
                    Notes
                </x-ui.table-head>

            </x-ui.table-header>

            <x-ui.table-body>

                @forelse($this->payments as $payment)

                    <x-ui.table-row>

                        <x-ui.table-cell>
                            {{ $payment->currency->symbol() }}
                            {{ number_format($payment->amount,2) }}
                        </x-ui.table-cell>

                        <x-ui.table-cell>
                            {{ $payment->receiver->name }}
                        </x-ui.table-cell>

                        <x-ui.table-cell>
                            {{ $payment->created_at->format('d M Y H:i') }}
                        </x-ui.table-cell>

                        <x-ui.table-cell>
                            {{ $payment->notes ?: '-' }}
                        </x-ui.table-cell>

                    </x-ui.table-row>

                @empty

                    <x-ui.table-row>

                        <x-ui.table-cell colspan="4" class="p-0">

                            <x-ui.empty-state
                                title="No payments recorded"
                                description="Payments will appear here after they are received."
                            />

                        </x-ui.table-cell>

                    </x-ui.table-row>

                @endforelse

            </x-ui.table-body>

        </x-ui.table>

    </x-ui.card>

</div>
