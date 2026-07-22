<?php

use App\Enums\CurrencyType;
use App\Enums\FeeMode;
use App\Enums\PaymentStatus;
use App\Enums\ReceiverMethod;
use App\Enums\TransferCalculationMode;
use App\Enums\TransferStatus;
use App\Models\Transfer;
use App\Services\TransferCalculatorService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts::app')] class extends Component {
    public Transfer $transfer;
    public $receiver_name;
    public $receiver_method;
    public $fee_mode;
    public $requested_currency;
    public $calculation_mode;
    public $requested_amount;
    public $customer_payable_amount;
    public $receiver_wallet_phone;
    public $receiver_account_number;
    public $customer_payable_currency;
    public $notes;
    public array|null $calculation = null;

    public function mount(Transfer $transfer)
    {
        Gate::authorize('update-transfer');
        $this->transfer = $transfer;
        if ($this->transfer->status !== TransferStatus::PENDING || $this->transfer->payment_status !== PaymentStatus::UNPAID) {
            abort(403, 'Only transfers with status "Pending " and "unpaid" can be updated.');
        }
        $this->receiver_name = $this->transfer->receiver_name;
        $this->receiver_method = $this->transfer->receiver_method->value;
        $this->fee_mode = $this->transfer->fee_mode->value;
        $this->requested_currency = $this->transfer->requested_currency->value;
        $this->receiver_wallet_phone = $this->transfer->receiver_wallet_phone;
        $this->receiver_account_number = $this->transfer->receiver_account_number;
        $this->customer_payable_currency = $this->transfer->customer_payable_currency->value;
        $this->calculation_mode = $this->transfer->calculation_mode->value;
        $this->notes = $this->transfer->notes;
        if ($this->calculation_mode === TransferCalculationMode::RECEIVER_AMOUNT->value) {
            $this->requested_amount = $this->transfer->requested_amount;
        } else {
            $this->customer_payable_amount = $this->transfer->customer_payable_amount;
        }
        $this->calculateIfReady();
    }

    public function updated($property)
    {
        if (in_array($property, [
            'requested_amount',
            'customer_payable_amount',
            'requested_currency',
            'customer_payable_currency',
            'fee_mode',
            'calculation_mode',
        ])) {
            $this->calculateIfReady();
        }
    }

    public function cancelEdit()
    {
        return redirect()->route('transfers.show', $this->transfer);
    }

    public function updatedCalculationMode()
    {
        $this->resetErrorBag();

        $this->calculation = null;
        $this->requested_amount = null;
        $this->customer_payable_amount = null;

        $this->fee_mode = FeeMode::INCLUDED->value;
    }


    private function calculateIfReady(): void
    {
        if (
            !$this->requested_currency ||
            !$this->customer_payable_currency
        ) {
            $this->calculation = null;
            return;
        }

        if (
            $this->calculation_mode === TransferCalculationMode::RECEIVER_AMOUNT->value &&
            !$this->requested_amount
        ) {
            $this->calculation = null;
            return;
        }

        if (
            $this->calculation_mode === TransferCalculationMode::CUSTOMER_PAYMENT->value &&
            !$this->customer_payable_amount
        ) {
            $this->calculation = null;
            return;
        }

        $this->calculateTransfer();
    }


    public function calculateTransfer()
    {
        $field = $this->calculation_mode === TransferCalculationMode::RECEIVER_AMOUNT->value
            ? 'requested_amount'
            : 'customer_payable_amount';

        $this->resetErrorBag($field);
        $this->calculation = null;

        try {

            if ($this->calculation_mode === TransferCalculationMode::RECEIVER_AMOUNT->value) {

                $this->calculation = app(TransferCalculatorService::class)
                    ->calculateFromReceiverAmount(
                        $this->requested_amount,
                        CurrencyType::from($this->requested_currency),
                        CurrencyType::from($this->customer_payable_currency),
                        FeeMode::from($this->fee_mode),
                    );

            } else {

                $this->calculation = app(TransferCalculatorService::class)
                    ->calculateFromCustomerPayment(
                        $this->customer_payable_amount,
                        CurrencyType::from($this->customer_payable_currency),
                        CurrencyType::from($this->requested_currency),
                    );

            }
        } catch (\Throwable $e) {
            $this->calculation = null;
            $this->addError($field, $e->getMessage());
        }
    }

    public function updateTransfer()
    {
        if ($this->transfer->status !== TransferStatus::PENDING || $this->transfer->payment_status !== PaymentStatus::UNPAID) {
            $this->addError(
                'general',
                'Only pending unpaid transfers can be updated.'
            );

            return;
        }

        $this->validate([
            'receiver_name' => 'required|string|max:255',
            'notes' => 'nullable|string|max:255',
            'fee_mode' => [
                'required',
                Rule::enum(FeeMode::class),
            ],
            'requested_currency' => [
                'required',
                Rule::enum(CurrencyType::class),
            ],
            'requested_amount' => [
                Rule::requiredIf(
                    $this->calculation_mode === TransferCalculationMode::RECEIVER_AMOUNT->value
                ),
                'nullable',
                'numeric',
                'min:0.01',
            ],

            'customer_payable_amount' => [
                Rule::requiredIf(
                    $this->calculation_mode === TransferCalculationMode::CUSTOMER_PAYMENT->value
                ),
                'nullable',
                'numeric',
                'min:0.01',
            ],
            'receiver_method' => [
                'required',
                Rule::enum(ReceiverMethod::class),
            ],
            'receiver_wallet_phone' => [
                Rule::requiredIf($this->receiver_method === ReceiverMethod::WALLET->value),
                'nullable',
                'string',
                'max:20',
            ],
            'receiver_account_number' => [
                Rule::requiredIf($this->receiver_method === ReceiverMethod::BANK->value),
                'nullable',
                'string',
                'max:20',
            ],
            'customer_payable_currency' => [
                'required',
                Rule::enum(CurrencyType::class),
            ],


        ]);
        $this->calculateTransfer();
        if ($this->calculation === null) {
            return;
        }
        $this->transfer->fill([
            'receiver_name' => $this->receiver_name,
            'notes' => $this->notes,
            'fee_mode' =>
                $this->calculation_mode === TransferCalculationMode::RECEIVER_AMOUNT->value
                    ? $this->fee_mode
                    : FeeMode::INCLUDED,
            'requested_currency' => $this->requested_currency,
            'requested_amount' =>
                $this->calculation_mode === TransferCalculationMode::RECEIVER_AMOUNT->value
                    ? $this->requested_amount
                    : $this->calculation['requested_amount'],
            'receiver_method' => $this->receiver_method,
            'receiver_wallet_phone' => $this->receiver_wallet_phone,
            'receiver_account_number' => $this->receiver_account_number,
            'transfer_amount' => $this->calculation['transfer_amount'],
            'customer_payable_amount' =>
                $this->calculation_mode === TransferCalculationMode::RECEIVER_AMOUNT->value
                    ? $this->calculation['customer_payable_amount']
                    : $this->customer_payable_amount,
            'customer_payable_currency' => $this->calculation['customer_payable_currency']->value,
            'commission_amount' => $this->calculation['commission_amount'],
            'commission_currency' => $this->calculation['commission_currency']->value,
            'remaining_amount' => $this->calculation['customer_payable_amount'],
            'calculation_mode' => $this->calculation_mode,

        ]);

        $this->transfer->save();
        session()->flash('success', 'Transfer updated successfully.');
        return redirect()->route(
            'transfers.show',
            $this->transfer
        );
    }

    public function updatedReceiverMethod()
    {
        if ($this->receiver_method === ReceiverMethod::BANK->value) {
            $this->receiver_wallet_phone = null;
        } elseif ($this->receiver_method === ReceiverMethod::WALLET->value) {
            $this->receiver_account_number = null;
        }
    }


};
?>

<div>
    <x-ui.page-header
        title="Update Transfer"
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



    <form
        wire:submit.prevent="updateTransfer"
        class="space-y-6"
    >

        <x-ui.form-section
            title="Receiver Information"
            description="Receiver details and delivery method."
        >

            <x-ui.select
                label="Calculation Mode"
                name="calculation_mode"
                wire:model.live="calculation_mode"
            >

                @foreach(TransferCalculationMode::cases() as $mode)

                    <option value="{{ $mode->value }}">
                        {{ str($mode->value)->replace('_',' ')->title() }}
                    </option>

                @endforeach

            </x-ui.select>

            <x-ui.input
                label="Receiver Name"
                name="receiver_name"
                wire:model="receiver_name"
            />

            <x-ui.select
                label="Receiver Method"
                name="receiver_method"
                wire:model.live="receiver_method"
            >

                @foreach(ReceiverMethod::cases() as $method)

                    <option value="{{ $method->value }}">
                        {{ $method->name }}
                    </option>

                @endforeach

            </x-ui.select>

            @if($receiver_method === ReceiverMethod::BANK->value)

                <x-ui.input
                    label="Receiver Bank Account Number"
                    name="receiver_account_number"
                    wire:model="receiver_account_number"
                />

            @elseif($receiver_method === ReceiverMethod::WALLET->value)

                <x-ui.input
                    label="Receiver Wallet Number"
                    name="receiver_wallet_phone"
                    wire:model="receiver_wallet_phone"
                />

            @endif

        </x-ui.form-section>

        <x-ui.form-section
            title="Transfer Information"
            description="Transfer calculation settings."
        >

            <x-ui.select
                label="Requested Currency"
                name="requested_currency"
                wire:model.live="requested_currency"
            >

                @foreach(CurrencyType::cases() as $currency)

                    <option value="{{ $currency->value }}">
                        {{ $currency->name }}
                    </option>

                @endforeach

            </x-ui.select>

            @if($calculation_mode === TransferCalculationMode::RECEIVER_AMOUNT->value)

                <x-ui.select
                    label="Fee Mode"
                    name="fee_mode"
                    wire:model.live="fee_mode"
                >

                    @foreach(FeeMode::cases() as $feeMode)

                        <option value="{{ $feeMode->value }}">
                            {{ $feeMode->name }}
                        </option>

                    @endforeach

                </x-ui.select>

                <x-ui.input
                    label="Receiver Amount"
                    name="requested_amount"
                    type="number"
                    step="0.01"
                    wire:model.live="requested_amount"
                />

            @else

                <x-ui.input
                    label="Customer Pay Amount"
                    name="customer_payable_amount"
                    type="number"
                    step="0.01"
                    wire:model.live="customer_payable_amount"
                />

            @endif

            <x-ui.select
                label="Customer Payable Currency"
                name="customer_payable_currency"
                wire:model.live="customer_payable_currency"
            >

                @foreach(CurrencyType::cases() as $currency)

                    <option value="{{ $currency->value }}">
                        {{ $currency->name }}
                    </option>

                @endforeach

            </x-ui.select>

        </x-ui.form-section>

        <x-ui.form-section
            title="Additional Information"
            class="grid-cols-1"
        >

            <x-ui.textarea
                label="Notes"
                name="notes"
                rows="4"
                wire:model="notes"
            />

        </x-ui.form-section>

        @if($calculation)

            <x-ui.card
                title="Calculation Preview"
                description="Live calculation based on the current values."
                class="mt-6"
            >

                @if($calculation_mode === TransferCalculationMode::RECEIVER_AMOUNT->value)

                    <div class="grid grid-cols-2 gap-y-4">

                        <div class="text-sm font-medium text-gray-500">
                            Receiver Gets
                        </div>

                        <div class="font-semibold">
                            {{ $this->calculation['transfer_amount'] }}
                            {{ CurrencyType::from($requested_currency)->symbol() }}
                        </div>

                        <div class="text-sm font-medium text-gray-500">
                            Customer Pays
                        </div>

                        <div class="font-semibold">
                            {{ $calculation['customer_payable_amount'] }}
                            {{ $calculation['customer_payable_currency']->symbol() }}
                        </div>

                        <div class="text-sm font-medium text-gray-500">
                            Commission
                        </div>

                        <div class="font-semibold text-green-600">
                            {{ $calculation['commission_amount'] }}
                            {{ $calculation['commission_currency']->symbol() }}
                        </div>

                    </div>

                @else

                    <div class="grid grid-cols-2 gap-y-4">

                        <div class="text-sm font-medium text-gray-500">
                            Customer Pays
                        </div>

                        <div class="font-semibold">
                            {{ $calculation['customer_payable_amount'] }}
                            {{ $calculation['customer_payable_currency']->symbol() }}
                        </div>

                        <div class="text-sm font-medium text-gray-500">
                            Receiver Gets
                        </div>

                        <div class="font-semibold">
                            {{ $this->calculation['transfer_amount'] }}
                            {{ CurrencyType::from($requested_currency)->symbol() }}
                        </div>

                        <div class="text-sm font-medium text-gray-500">
                            Commission
                        </div>

                        <div class="font-semibold text-green-600">
                            {{ $calculation['commission_amount'] }}
                            {{ $calculation['commission_currency']->symbol() }}
                        </div>

                    </div>

                @endif
            </x-ui.card>

        @endif



        <div class="flex justify-end gap-3">

            <x-ui.button
                type="button"
                variant="secondary"
                wire:click="cancelEdit"
            >
                Cancel
            </x-ui.button>

            <x-ui.button
                type="submit"
            >
                Update Transfer
            </x-ui.button>

        </div>

    </form>

</div>
