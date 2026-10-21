<?php

use App\Enums\CurrencyType;
use App\Enums\PaymentStatus;
use App\Enums\ReceiverMethod;
use App\Enums\TransferCalculationMode;
use App\Enums\TransferStatus;
use App\Services\TransferCalculatorService;
use App\Services\TransferManagementService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

new  #[Layout('layouts::app')]
class extends Component {
    public $receiver_name;
    public $notes;
    public $requested_currency;
    public $requested_amount;
    public $receiver_method;
    public $receiver_wallet_phone;
    public $receiver_account_number;
    public $customer_payable_currency;
    public $customer_payable_amount;
    public $initial_customer_pay_amount = 0;
    public $commission_amount;
    public TransferCalculationMode $calculationMode =
        TransferCalculationMode::RECEIVER_AMOUNT;
    public array|null $calculation = null;

    public function mount()
    {
        Gate::authorize('create-transfer');
    }

    public function updated($property)
    {

        if (in_array($property, [
            'requested_amount',
            'requested_currency',
            'customer_payable_currency',
            'calculationMode',
            'customer_payable_amount',
            'commission_amount'
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
                !$this->requested_currency ||
                $this->commission_amount === null
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
            $this->commission_amount === null
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
                        (float) ($this->commission_amount)
                    );

            } else {

                $this->calculation = app(TransferCalculatorService::class)
                    ->calculateFromReceiverAmount(
                        $this->requested_amount,
                        CurrencyType::from($this->requested_currency),
                        CurrencyType::from($this->customer_payable_currency),
                        (float) ($this->commission_amount)
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

            $this->addError(
                $field,
                __('transfers.errors.calculation_failed')
            );

            return;
        }

        $this->validate([
            'receiver_name' => 'required|string|max:255',
            'receiver_method' => [
                'required',
                Rule::enum(ReceiverMethod::class),
            ],
            'notes' => 'nullable|string|max:255',
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
            'commission_amount' => 'required|numeric|min:0',

        ]);
        try {
            $transfer = app(TransferManagementService::class)->create([
                'receiver_name' => $this->receiver_name,
                'receiver_method' => $this->receiver_method,
                'notes' => $this->notes,
                'requested_currency' => $this->requested_currency,
                'requested_amount' => $this->requested_amount,
                'receiver_wallet_phone' => $this->receiver_wallet_phone,
                'receiver_account_number' => $this->receiver_account_number,
                'customer_payable_currency' => $this->customer_payable_currency,
                'customer_payable_amount' => $this->customer_payable_amount,
                'initial_customer_pay_amount' => $this->initial_customer_pay_amount,
                'commission_amount' => $this->commission_amount,
                'calculation_mode' => $this->calculationMode->value,
            ], auth()->user());
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        } catch (\Exception $e) {
            $this->addError('initial_customer_pay_amount', $e->getMessage());

            return;
        }

        session()->flash(
            'success',
            __('transfers.messages.created')
        );

        return redirect()->route(
            'transfers.show', $transfer
        );
    }
};
?>

<div>
    <x-ui.page-header
        :title="__('transfers.page.title')"

        :description="__('transfers.page.description')"
    >
        <x-slot:actions>

            <x-ui.button
                :href="route('transfers.index')"
                variant="secondary"
            >
                {{ app()->getLocale() === 'ar' ? '→' : '←' }}
                {{ __('transfers.buttons.back') }}
            </x-ui.button>

        </x-slot:actions>
    </x-ui.page-header>


    <form
        wire:submit.prevent="createTransfer"
        class="space-y-6"
    >

        <x-ui.form-section
            :title="__('transfers.receiver.title')"

            :description="__('transfers.receiver.description')"
        >

            <x-ui.select
                :label="__('transfers.fields.calculation_mode')"
                name="calculationMode"
                wire:model.live="calculationMode"
            >


                @foreach(TransferCalculationMode::cases() as $mode)

                    <option value="{{ $mode->value }}">
                        {{ $mode->label() }}
                    </option>

                @endforeach

            </x-ui.select>

            <x-ui.input
                :label="__('transfers.fields.receiver_name')"
                name="receiver_name"
                wire:model="receiver_name"
            />

            <x-ui.select
                :label="__('transfers.fields.receiver_method')"
                name="receiver_method"
                wire:model.live="receiver_method"
            >

                <option value="">
                    {{ __('transfers.placeholders.select_receiver_method') }}
                </option>

                @foreach(ReceiverMethod::cases() as $method)

                    <option value="{{ $method->value }}">
                        {{ $method->label() }}
                    </option>

                @endforeach

            </x-ui.select>

            @if($receiver_method === ReceiverMethod::BANK->value)

                <x-ui.input
                    :label="__('transfers.fields.receiver_account_number')"
                    name="receiver_account_number"
                    wire:model="receiver_account_number"
                />

            @elseif($receiver_method === ReceiverMethod::WALLET->value)

                <x-ui.input
                    :label="__('transfers.fields.receiver_wallet_number')"
                    name="receiver_wallet_phone"
                    wire:model="receiver_wallet_phone"
                />

            @endif

        </x-ui.form-section>

        <x-ui.form-section
            :title="__('transfers.transfer.title')"
            :description="__('transfers.transfer.description')"
        >

            <x-ui.select
                :label="__('transfers.fields.requested_currency')"
                name="requested_currency"
                wire:model.live="requested_currency"
            >

                <option value="">
                    {{ __('transfers.placeholders.select_requested_currency') }}
                </option>

                @foreach(CurrencyType::cases() as $currency)

                    <option value="{{ $currency->value }}">
                        {{ $currency->label() }}
                    </option>

                @endforeach

            </x-ui.select>

            @if($calculationMode === TransferCalculationMode::RECEIVER_AMOUNT)

                <x-ui.input
                    :label="__('transfers.fields.requested_amount')"
                    name="requested_amount"
                    type="number"
                    step="0.01"
                    wire:model.live="requested_amount"
                />

            @else

                <x-ui.input
                    :label="__('transfers.fields.customer_pay_amount')"
                    name="customer_payable_amount"
                    type="number"
                    step="0.01"
                    wire:model.live="customer_payable_amount"
                />

            @endif

            <x-ui.select
                :label="__('transfers.fields.customer_pay_currency')"
                name="customer_payable_currency"
                wire:model.live="customer_payable_currency"
            >

                <option value="">
                    {{ __('transfers.placeholders.select_customer_pay_currency') }}
                </option>

                @foreach(CurrencyType::cases() as $currency)

                    <option value="{{ $currency->value }}">
                        {{ $currency->label() }}
                    </option>

                @endforeach

            </x-ui.select>

            <x-ui.input
                :label="__('transfers.fields.commission')"
                name="commission_amount"
                type="number"
                step="0.01"
                wire:model.live="commission_amount"
            />

        </x-ui.form-section>

        <x-ui.form-section
            :title="__('transfers.payment.title')"

            :description="__('transfers.payment.description')"
            class="grid-cols-1"
        >

            <x-ui.input
                :label="__('transfers.fields.initial_payment')"
                name="initial_customer_pay_amount"
                type="number"
                step="0.01"
                wire:model.live="initial_customer_pay_amount"
            />

        </x-ui.form-section>


        <x-ui.form-section
            :title="__('transfers.additional.title')"
            class="grid-cols-1"
        >

            <x-ui.textarea
                :label="__('transfers.fields.notes')"
                name="notes"
                rows="4"
                wire:model="notes"
            />

        </x-ui.form-section>

        @if($calculation)

            <x-ui.card
                :title="__('transfers.preview.title')"

                :description="__('transfers.preview.description')"
                class="mt-6"
            >

                @if($calculationMode === TransferCalculationMode::RECEIVER_AMOUNT->value)

                    <div class="grid grid-cols-2 gap-y-4 items-center">

                        <div class="text-sm font-medium text-gray-500 text-start">
                            {{ __('transfers.preview.receiver_gets') }}
                        </div>

                        <div class="font-semibold text-start">
                            {{ $this->calculation['transfer_amount'] }}
                            {{ CurrencyType::from($requested_currency)->symbol() }}
                        </div>

                        <div class="text-sm font-medium text-gray-500 text-start">
                            {{ __('transfers.preview.customer_pays') }}
                        </div>

                        <div class="font-semibold text-start">
                            {{ $calculation['customer_payable_amount'] }}
                            {{ $calculation['customer_payable_currency']->symbol() }}
                        </div>

                        <div class="text-sm font-medium text-gray-500 text-start">
                            {{ __('transfers.preview.commission') }}
                        </div>

                        <div class="font-semibold text-green-600">
                            {{ $calculation['commission_amount'] }}
                            {{ $calculation['commission_currency']->symbol() }}
                        </div>

                    </div>

                @else

                    <div class="grid grid-cols-2 gap-y-4 items-center">

                        <div class="text-sm font-medium text-gray-500 text-start">
                            {{ __('transfers.preview.customer_pays') }}
                        </div>

                        <div class="font-semibold text-start">
                            {{ $calculation['customer_payable_amount'] }}
                            {{ $calculation['customer_payable_currency']->symbol() }}
                        </div>

                        <div class="text-sm font-medium text-gray-500 text-start">
                            {{ __('transfers.preview.receiver_gets') }}
                        </div>

                        <div class="font-semibold text-start">
                            {{ $this->calculation['transfer_amount'] }}
                            {{ CurrencyType::from($requested_currency)->symbol() }}
                        </div>

                        <div class="text-sm font-medium text-gray-500 text-start">
                            {{ __('transfers.preview.commission') }}
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
                {{ __('transfers.buttons.cancel') }}
            </x-ui.button>

            <x-ui.button
                type="submit"
            >
                {{ __('transfers.buttons.create') }}
            </x-ui.button>

        </div>


    </form>
</div>
