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
        Gate::authorize('receive-payment');
        $this->transfer = $transfer;
    }

    #[Computed]
    public function payments()
    {
        return $this->transfer->payments()->latest()->get();
    }


    public function receivePayment()
    {
        Gate::authorize('receive-payment');

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
        session()->flash(
            'success',
            __('receive_payment.messages.received')
        );
        return redirect()->route('transfers.show', $this->transfer);
    }
};
?>

<div>

    <x-ui.page-header
        :title="__('receive_payment.page.title')"
        :description="$transfer->reference_number"
    >
        <x-slot:actions>

            <x-ui.button
                :href="route('transfers.show', $transfer)"
                variant="secondary"
            >
                ← {{ __('receive_payment.buttons.back') }}
            </x-ui.button>

        </x-slot:actions>
    </x-ui.page-header>
    <x-ui.flash/>

    <x-ui.card
        :title="__('receive_payment.summary.title')"
        :description="__('receive_payment.summary.description')"
        class="mb-2"
    >

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

            <div>
                <div class="text-sm text-gray-500">
                    {{ __('receive_payment.summary.requested_amount') }}
                </div>

                <div class="text-lg font-semibold">
                    {{ $transfer->customer_payable_currency->symbol() }}
                    {{ number_format($transfer->customer_payable_amount,2) }}
                </div>
            </div>

            <div>
                <div class="text-sm text-gray-500">
                    {{ __('receive_payment.summary.paid') }}
                </div>

                <div class="text-lg font-semibold text-green-600">
                    {{ $transfer->customer_payable_currency->symbol() }}
                    {{ number_format($transfer->paid_amount,2) }}
                </div>
            </div>

            <div>
                <div class="text-sm text-gray-500">
                    {{ __('receive_payment.summary.remaining') }}
                </div>

                <div class="text-lg font-semibold text-red-600">
                    {{ $transfer->customer_payable_currency->symbol() }}
                    {{ number_format($transfer->remaining_amount,2) }}
                </div>
            </div>

        </div>

    </x-ui.card>

    <x-ui.form-section
        :title="__('receive_payment.form.title')"
        :description="__('receive_payment.form.description')"
    >
        <div class="col-span-1 md:col-span-2 flex justify-center">

            <div class="w-full max-w-2xl">
        <form
            wire:submit.prevent="receivePayment"
            class="space-y-5"
        >

            <x-ui.input
                :label="__('receive_payment.fields.payment_amount')"
                name="payment_amount"
                type="number"
                step="0.01"
                wire:model="payment_amount"
            />

            <x-ui.textarea
                :label="__('receive_payment.fields.notes')"
                name="notes"
                rows="3"
                wire:model="notes"
            />

            <div class="flex justify-end gap-3">

                <x-ui.button
                    :href="route('transfers.show',$transfer)"
                    variant="secondary"
                >
                    {{ __('receive_payment.buttons.cancel') }}
                </x-ui.button>

                <x-ui.button type="submit">
                    {{ __('receive_payment.buttons.receive') }}
                </x-ui.button>

            </div>

        </form>
        </div>
        </div>

    </x-ui.form-section>

    <x-ui.card
        :title="__('receive_payment.history.title')"
        :description="__('receive_payment.history.description')"
        class="mt-2"
    >

        <x-ui.table>

            <x-ui.table-header>

                <x-ui.table-head>
                    {{ __('receive_payment.history.amount') }}
                </x-ui.table-head>

                <x-ui.table-head>
                    {{ __('receive_payment.history.received_by') }}
                </x-ui.table-head>

                <x-ui.table-head>
                    {{ __('receive_payment.history.date') }}
                </x-ui.table-head>

                <x-ui.table-head>
                    {{ __('receive_payment.history.notes') }}
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
                                :title="__('receive_payment.empty_state.title')"
                                :description="__('receive_payment.empty_state.description')"
                            />

                        </x-ui.table-cell>

                    </x-ui.table-row>

                @endforelse

            </x-ui.table-body>

        </x-ui.table>

    </x-ui.card>

</div>
