<?php

use App\Enums\FeeMode;
use App\Enums\CurrencyType;
use App\Enums\PaymentStatus;
use App\Enums\ReceiverMethod;
use App\Enums\TransferStatus;
use App\Models\Payment;
use App\Models\Transfer;
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
    public $customer_pay_currency;
    public array|null $calculation = null;

    public function updatedRequestedAmount($value)
    {
        $this->calculateIfReady();
    }

    public function updatedFeeMode($value)
    {
        $this->calculateIfReady();
    }

    public function updatedRequestedCurrency($value)
    {
        $this->calculateIfReady();
    }

    public function updatedCustomerPayCurrency($value)
    {
        $this->calculateIfReady();
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
        if (
            !$this->requested_amount ||
            !$this->requested_currency ||
            !$this->customer_pay_currency
        ) {
            $this->calculation = null;
            return;
        }

        $this->calculateTransfer();
    }

    public function calculateTransfer()
    {
        $calculation = app(TransferCalculatorService::class)->calculate(
            $this->requested_amount,
            CurrencyType::from($this->requested_currency),
            CurrencyType::from($this->customer_pay_currency),
            FeeMode::from($this->fee_mode)
        );

        $this->calculation = $calculation;
    }


    public function createTransfer()
    {
        $this->calculateIfReady();
        if ($this->calculation === null) {
            $this->addError(
                'requested_amount',
                'Unable to calculate the transfer.'
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
            'fee_mode' => [
                'required',
                Rule::enum(FeeMode::class),
            ],
            'requested_currency' => [
                'required',
                Rule::enum(CurrencyType::class),
            ],
            'requested_amount' => 'required|numeric|min:0.01',
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
            'customer_pay_currency' => [
                'required',
                Rule::enum(CurrencyType::class),
            ],

        ]);

        DB::transaction(function () {

            $transfer = new Transfer();
            $transfer->fill([
                'receiver_name' => $this->receiver_name,
                'receiver_method' => $this->receiver_method,
                'notes' => $this->notes,
                'fee_mode' => $this->fee_mode,
                'requested_currency' => $this->requested_currency,
                'requested_amount' => $this->requested_amount,
                'receiver_wallet_phone' => $this->receiver_wallet_phone,
                'receiver_account_number' => $this->receiver_account_number,
                'customer_payable_amount' => $this->calculation['customer_payable_amount'],
                'customer_payable_currency' => $this->calculation['customer_payable_currency'],
                'commission_amount' => $this->calculation['commission_amount'],
                'commission_currency' => $this->calculation['commission_currency'],
            ]);

            $transfer->reference_number = Str::upper('TRF-' . now()->format('Ymd') . '-' . Str::random(6));
            $transfer->created_by = auth()->id();
            $transfer->status = TransferStatus::PENDING;
            $transfer->save();

        });
        session()->flash('message', 'Transfer created successfully.');
        $this->reset(['receiver_name', 'receiver_method', 'notes', 'fee_mode', 'requested_currency', 'requested_amount', 'receiver_wallet_phone', 'receiver_account_number', 'customer_pay_currency','calculation']);
        return redirect()->route('transfers.index');
    }
};
?>

<div>
    <div>
        <h1>create Transfer</h1>
        <form wire:submit="createTransfer">

            <div>
                <label>Receiver Name</label>
                <input type="text" wire:model="receiver_name">
                @error('receiver_name') <span>{{ $message }}</span> @enderror
            </div>

            <div>
                <label>Receiver Method</label>
                <select wire:model.live="receiver_method">
                    <option value="">Select Receiver Method</option>
                    @foreach(ReceiverMethod::cases() as $method)
                        <option value="{{ $method->value }}">{{ $method->name }}</option>
                    @endforeach
                </select>
                @error('receiver_method') <span>{{ $message }}</span> @enderror
            </div>

            @if($receiver_method === ReceiverMethod::BANK->value)
                <div>
                    <label>Receiver Bank Account Number</label>
                    <input type="text" wire:model="receiver_account_number">
                    @error('receiver_account_number') <span>{{ $message }}</span> @enderror
                </div>
            @endif
            @if($receiver_method === ReceiverMethod::WALLET->value)
                <div>
                    <label>Receiver Wallet Number</label>
                    <input type="text" wire:model="receiver_wallet_phone">
                    @error('receiver_wallet_phone') <span>{{ $message }}</span> @enderror
                </div>
            @endif


            <div>
                <label>Fee Mode</label>
                <select wire:model.live="fee_mode">
                    <option value="">Select Fee Mode</option>
                    @foreach(FeeMode::cases() as $mode)
                        <option value="{{ $mode->value }}">{{ $mode->name }}</option>
                    @endforeach
                </select>
                @error('fee_mode') <span>{{ $message }}</span> @enderror
            </div>

            <div>
                <label>Requested Currency</label>
                <select wire:model.live="requested_currency">
                    <option value="">Select Requested Currency</option>
                    @foreach(CurrencyType::cases() as $currency)
                        <option value="{{ $currency->value }}">{{ $currency->name }}</option>
                    @endforeach
                </select>
                @error('requested_currency') <span>{{ $message }}</span> @enderror
            </div>

            <div>
                <label>Requested Amount</label>
                <input type="number" step="0.01" wire:model.live="requested_amount">
                @error('requested_amount') <span>{{ $message }}</span> @enderror
            </div>

            <div>
                <label>Customer Pay Currency</label>
                <select wire:model.live="customer_pay_currency">
                    <option value="">Select Customer Pay Currency</option>
                    @foreach(CurrencyType::cases() as $currency)
                        <option value="{{ $currency->value }}">{{ $currency->name }}</option>
                    @endforeach
                </select>
                @error('customer_pay_currency') <span>{{ $message }}</span> @enderror
            </div>
            <div>
                <label>Notes</label>
                <textarea wire:model="notes"></textarea>
                @error('notes') <span>{{ $message }}</span> @enderror
            </div>


            <button type="submit">Create Transfer</button>

        </form>
        <div>Customer Pays:
            {{ $this->calculation['customer_payable_amount'] ?? 0 }}
            {{ $this->calculation['customer_payable_currency'] ?? '' }}
        </div>
        <div>Commission Amount:
            {{ $this->calculation['commission_amount'] ?? 0 }} {{  $this->calculation['commission_currency'] ?? ''  }}
        </div>
    </div>
</div>
