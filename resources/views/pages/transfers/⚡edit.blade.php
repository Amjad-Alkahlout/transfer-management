<?php

use App\Enums\CurrencyType;
use App\Enums\FeeMode;
use App\Enums\PaymentStatus;
use App\Enums\ReceiverMethod;
use App\Enums\TransferStatus;
use App\Models\Transfer;
use App\Services\TransferCalculatorService;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {
    public Transfer $transfer;
    public $receiver_name;
    public $receiver_method;
    public $fee_mode;
    public $requested_currency;
    public $requested_amount;
    public $receiver_wallet_phone;
    public $receiver_account_number;
    public $customer_payable_currency;
    public $notes;
    public array|null $calculation=null;

    public function mount(Transfer $transfer)
    {
        $this->transfer = $transfer;
        if ($this->transfer->status !== TransferStatus::PENDING || $this->transfer->payment_status !== PaymentStatus::UNPAID) {
            abort(403, 'Only transfers with status "Pending " and "unpaid" can be updated.');
        }
        $this->receiver_name = $this->transfer->receiver_name;
        $this->receiver_method = $this->transfer->receiver_method->value;
        $this->fee_mode = $this->transfer->fee_mode->value;
        $this->requested_currency = $this->transfer->requested_currency->value;
        $this->requested_amount = $this->transfer->requested_amount;
        $this->receiver_wallet_phone = $this->transfer->receiver_wallet_phone;
        $this->receiver_account_number = $this->transfer->receiver_account_number;
        $this->customer_payable_currency = $this->transfer->customer_payable_currency->value;
        $this->notes = $this->transfer->notes;
        $this->calculateIfReady();
    }


    private function calculateIfReady(): void
    {
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
        try {
            $this->calculation = app(TransferCalculatorService::class)->calculate(
                $this->requested_amount,
                CurrencyType::from($this->requested_currency),
                CurrencyType::from($this->customer_payable_currency),
                FeeMode::from($this->fee_mode)
            );
        } catch (\Throwable $e) {
            $this->calculation = null;
            $this->addError('requested_amount', $e->getMessage());
        }
    }

    public function updateTransfer()
    {
        if ($this->transfer->status !== TransferStatus::PENDING || $this->transfer->payment_status !== PaymentStatus::UNPAID) {
            abort(403, 'Only transfers with status "Pending " and "unpaid" can be updated.');
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
            'requested_amount' => 'required|numeric|min:0.01',
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
            'fee_mode' => $this->fee_mode,
            'requested_currency' => $this->requested_currency,
            'requested_amount' => $this->requested_amount,
            'receiver_method' => $this->receiver_method,
            'receiver_wallet_phone' => $this->receiver_wallet_phone,
            'receiver_account_number' => $this->receiver_account_number,
            'transfer_amount' => $this->calculation['transfer_amount'],
            'customer_payable_amount' => $this->calculation['customer_payable_amount'],
            'customer_payable_currency' => $this->calculation['customer_payable_currency'],
            'commission_amount' => $this->calculation['commission_amount'],
            'commission_currency' => $this->calculation['commission_currency'],
            'remaining_amount' => $this->calculation['customer_payable_amount'],

        ]);

        $this->transfer->save();
        session()->flash('message', 'Transfer updated successfully.');
        return redirect()->route('transfers.index');
    }

    public function updatedReceiverMethod()
    {
        if ($this->receiver_method === ReceiverMethod::BANK->value) {
            $this->receiver_wallet_phone = null;
        } elseif ($this->receiver_method === ReceiverMethod::WALLET->value) {
            $this->receiver_account_number = null;
        }
    }
    public function updated($property)
    {
        if (in_array($property, [
            'requested_amount',
            'requested_currency',
            'customer_payable_currency',
            'fee_mode',
        ])) {
            $this->calculateIfReady();
        }
    }

};
?>

<div>
    <div>Update Transfer</div>

    @if(session()->has('message'))
        <div>{{ session('message') }}</div>
    @endif

    <form wire:submit.prevent="updateTransfer">
        <div>
            <label>Receiver Name</label>
            <input type="text" wire:model="receiver_name">
            @error('receiver_name') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label>Receiver Method</label>
            <select wire:model.live="receiver_method">
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
        @elseif($receiver_method === ReceiverMethod::WALLET->value)
            <div>
                <label>Receiver Wallet Number</label>
                <input type="text" wire:model="receiver_wallet_phone">
                @error('receiver_wallet_phone') <span>{{ $message }}</span> @enderror
            </div>
        @endif

        <div>
            <label>Fee Mode</label>
            <select wire:model.live="fee_mode">
                @foreach(FeeMode::cases() as $feeMode)
                    <option value="{{ $feeMode->value }}">{{ $feeMode->name }}</option>
                @endforeach
            </select>
            @error('fee_mode') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label>Requested Currency</label>
            <select wire:model.live="requested_currency">
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
            <label>Customer Payable Currency</label>
            <select wire:model.live="customer_payable_currency">
                @foreach(CurrencyType::cases() as $currency)
                    <option value="{{ $currency->value }}">{{ $currency->name }}</option>
                @endforeach
            </select>
            @error('customer_payable_currency') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label>Notes</label>
            <input type="text" wire:model="notes">
            @error('notes') <span>{{ $message }}</span> @enderror
        </div>

        <button type="submit">Update Transfer</button>
    </form>
    @if($calculation)
        <div>
            Customer Payable Amount:
            {{ $calculation['customer_payable_amount'] }}
            {{ $calculation['customer_payable_currency']->name }}
        </div>

        <div>
            Commission:
            {{ $calculation['commission_amount'] }}
            {{ $calculation['commission_currency']->name }}
        </div>

        <div>Transfer Amount:
            {{ $this->calculation['transfer_amount'] }} {{  $transfer->requested_currency->name }}
        </div>
    @endif
</div>
