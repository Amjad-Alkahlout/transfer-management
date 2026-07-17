<?php

use App\Enums\FeeMode;
use App\Enums\CurrencyType;
use App\Enums\PaymentStatus;
use App\Enums\ReceiverMethod;
use App\Enums\TransferCalculationMode;
use App\Enums\TransferStatus;
use App\Models\Payment;
use App\Models\Transfer;
use App\Services\ReceivePaymentService;
use App\Services\TransferCalculatorService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

new  #[Layout('layouts::app')]
class extends Component {
    public $receiver_name;
    public $notes;
    public $fee_mode;
    public $requested_currency;
    public $requested_amount;
    public $receiver_method;
    public $receiver_wallet_phone;
    public $receiver_account_number;
    public $customer_payable_currency;
    public $customer_payable_amount;
    public $initial_customer_pay_amount = 0;
    public TransferCalculationMode $calculationMode =
        TransferCalculationMode::RECEIVER_AMOUNT;
    public array|null $calculation = null;


    public function updated($property)
    {

        if (in_array($property, [
            'requested_amount',
            'requested_currency',
            'customer_payable_currency',
            'fee_mode',
            'calculationMode',
            'customer_payable_amount'
        ])) {
            $this->calculateIfReady();
        }
    }

    public function updatedCalculationMode()
    {
        $this->resetErrorBag();

        $this->calculation = null;
        $this->requested_currency = null;
        $this->customer_payable_currency = null;
        $this->requested_amount = null;
        $this->customer_payable_amount = null;
        $this->initial_customer_pay_amount = 0;

        $this->fee_mode = FeeMode::INCLUDED->value;
    }


    public function updatedReceiverMethod($value)
    {
        if ($value === ReceiverMethod::BANK->value) {
            $this->receiver_wallet_phone = null;
        } elseif ($value === ReceiverMethod::WALLET->value) {
            $this->receiver_account_number = null;
        }
    }


    private function calculateIfReady(): void
    {
        if ($this->calculationMode === TransferCalculationMode::CUSTOMER_PAYMENT) {

            if (
                !$this->customer_payable_amount ||
                !$this->customer_payable_currency ||
                !$this->requested_currency
            ) {
                $this->calculation = null;
                return;
            }

            $this->calculateTransfer();

            return;
        }

        if (
            !$this->requested_amount ||
            !$this->requested_currency ||
            !$this->customer_payable_currency ||
            !$this->fee_mode
        ) {
            $this->calculation = null;
            return;
        }

        $this->calculateTransfer();
    }


    public function calculateTransfer()
    {
        $this->resetErrorBag([
            'requested_amount',
            'customer_payable_amount',
        ]);

        $this->calculation = null;

        try {

            if ($this->calculationMode === TransferCalculationMode::CUSTOMER_PAYMENT) {

                $this->calculation = app(TransferCalculatorService::class)
                    ->calculateFromCustomerPayment(
                        $this->customer_payable_amount,
                        CurrencyType::from($this->customer_payable_currency),
                        CurrencyType::from($this->requested_currency),
                    );

            } else {

                $this->calculation = app(TransferCalculatorService::class)
                    ->calculateFromReceiverAmount(
                        $this->requested_amount,
                        CurrencyType::from($this->requested_currency),
                        CurrencyType::from($this->customer_payable_currency),
                        FeeMode::from($this->fee_mode),
                    );
            }

        } catch (\Throwable $e) {

            $this->calculation = null;

            if ($this->calculationMode === TransferCalculationMode::CUSTOMER_PAYMENT) {
                $this->addError('customer_payable_amount', $e->getMessage());
            } else {
                $this->addError('requested_amount', $e->getMessage());
            }
        }
    }


    public function createTransfer()
    {
        $this->calculateIfReady();

        if ($this->calculation === null) {
            $field = $this->calculationMode === TransferCalculationMode::CUSTOMER_PAYMENT
                ? 'customer_payable_amount'
                : 'requested_amount';

            $this->addError($field, 'Unable to calculate the transfer.');

            return;
        }

        $this->validate([
            'receiver_name' => 'required|string|max:255',
            'receiver_method' => [
                'required',
                Rule::enum(ReceiverMethod::class),
            ],
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
                Rule::requiredIf($this->calculationMode === TransferCalculationMode::RECEIVER_AMOUNT),
                'nullable',
                'numeric',
                'min:0.01'],

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
            'customer_payable_amount' => [
                Rule::requiredIf($this->calculationMode === TransferCalculationMode::CUSTOMER_PAYMENT),
                'nullable',
                'numeric',
                'min:0.01',
            ],
            'initial_customer_pay_amount' => 'nullable|numeric|min:0|max:' . $this->calculation['customer_payable_amount'],

        ]);


        DB::transaction(function () {

            $transfer = new Transfer();
            $transfer->fill([
                'receiver_name' => $this->receiver_name,
                'receiver_method' => $this->receiver_method,
                'notes' => $this->notes,
                'fee_mode' => $this->calculationMode === TransferCalculationMode::RECEIVER_AMOUNT
                    ? $this->fee_mode
                    : FeeMode::INCLUDED,
                'requested_currency' => $this->requested_currency,
                'requested_amount' => $this->calculationMode === TransferCalculationMode::RECEIVER_AMOUNT
                    ? $this->requested_amount
                    : $this->calculation['requested_amount'],
                'receiver_wallet_phone' => $this->receiver_wallet_phone,
                'receiver_account_number' => $this->receiver_account_number,
                'transfer_amount' => $this->calculation['transfer_amount'],
                'customer_payable_amount' => $this->calculation['customer_payable_amount'],
                'customer_payable_currency' => $this->calculation['customer_payable_currency']->value,
                'commission_amount' => $this->calculation['commission_amount'],
                'commission_currency' => $this->calculation['commission_currency']->value,
                'paid_amount' => 0,
                'calculation_mode' => $this->calculationMode,
            ]);

            $transfer->reference_number = Str::upper('TRF-' . now()->format('Ymd') . '-' . Str::random(6));
            $transfer->created_by = auth()->id();
            $transfer->status = TransferStatus::PENDING;

            $transfer->payment_status = PaymentStatus::UNPAID;
            $transfer->remaining_amount =
                $this->calculation['customer_payable_amount'];

            $transfer->save();

            try{
            if ($this->initial_customer_pay_amount > 0) {

                app(ReceivePaymentService::class)->receive(
                    $transfer,
                    $this->initial_customer_pay_amount,
                    'Initial payment',
                );

            }

            } catch (\Throwable $e) {
                $this->addError('initial_customer_pay_amount', $e->getMessage());
                throw $e;
            }

        });
        session()->flash('message', 'Transfer created successfully.');
        $this->reset(['receiver_name', 'receiver_method', 'notes', 'fee_mode', 'requested_currency', 'requested_amount', 'receiver_wallet_phone', 'receiver_account_number', 'customer_payable_currency', 'calculation', 'initial_customer_pay_amount', 'customer_payable_amount']);
        $this->calculationMode = TransferCalculationMode::RECEIVER_AMOUNT;
        $this->fee_mode = FeeMode::INCLUDED->value;
        return redirect()->route('transfers.index');
    }
};
?>

 <div>
    <x-ui.page-header
        title="Create New Transfer"
        description="Create a new customer transfer."
    >
        <x-slot:actions>

            <x-ui.button
                :href="route('transfers.index')"
                variant="secondary"
            >
                ← Back
            </x-ui.button>

        </x-slot:actions>
    </x-ui.page-header>

    <form
        wire:submit.prevent="createTransfer"
        class="space-y-6"
    >

        <x-ui.form-section
            title="Receiver Information"
            description="Receiver details and delivery method."
        >

            <x-ui.select
                label="Calculation Mode"
                name="calculationMode"
                wire:model.live="calculationMode"
            >

                @foreach(TransferCalculationMode::cases() as $mode)

                    <option value="{{ $mode->value }}">
                        {{ $mode->name }}
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

                <option value="">
                    Select Receiver Method
                </option>

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

                <option value="">
                    Select Requested Currency
                </option>

                @foreach(CurrencyType::cases() as $currency)

                    <option value="{{ $currency->value }}">
                        {{ $currency->name }}
                    </option>

                @endforeach

            </x-ui.select>

            @if($calculationMode === TransferCalculationMode::RECEIVER_AMOUNT)

                <x-ui.input
                    label="Requested Amount"
                    name="requested_amount"
                    type="number"
                    step="0.01"
                    wire:model.live="requested_amount"
                />

                <x-ui.select
                    label="Fee Mode"
                    name="fee_mode"
                    wire:model.live="fee_mode"
                >

                    <option value="">
                        Select Fee Mode
                    </option>

                    @foreach(FeeMode::cases() as $mode)

                        <option value="{{ $mode->value }}">
                            {{ $mode->name }}
                        </option>

                    @endforeach

                </x-ui.select>

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
                label="Customer Pay Currency"
                name="customer_payable_currency"
                wire:model.live="customer_payable_currency"
            >

                <option value="">
                    Select Customer Pay Currency
                </option>

                @foreach(CurrencyType::cases() as $currency)

                    <option value="{{ $currency->value }}">
                        {{ $currency->name }}
                    </option>

                @endforeach

            </x-ui.select>

        </x-ui.form-section>

        <x-ui.form-section
            title="Payment"
            description="Record an optional payment received during transfer creation."
            class="grid-cols-1"
        >

            <x-ui.input
                label="Initial Payment"
                name="initial_customer_pay_amount"
                type="number"
                step="0.01"
                wire:model.live="initial_customer_pay_amount"
            />

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
            >

                @if($calculationMode === TransferCalculationMode::RECEIVER_AMOUNT->value)

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
                    :href="route('transfers.index')"
                    variant="secondary"
                >
                    Cancel
                </x-ui.button>

                <x-ui.button
                    type="submit"
                >
                    Create Transfer
                </x-ui.button>

            </div>



        </form>
</div>
