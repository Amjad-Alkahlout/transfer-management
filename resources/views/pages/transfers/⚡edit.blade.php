<?php

use App\Enums\CurrencyType;
use App\Enums\FeeMode;
use App\Enums\ReceiverMethod;
use App\Enums\TransferStatus;
use App\Models\Transfer;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {
    public Transfer $transfer;
    public $receiver_name;
    public $notes;
    public $fee_mode;
    public $requested_currency;
    public $requested_amount;
    public $receiver_method;
    public $receiver_wallet_phone;
    public $receiver_account_number;

    public function mount(Transfer $transfer)
    {
        $this->transfer = $transfer;
        if ($this->transfer->status !== TransferStatus::PENDING_PRICING && $this->transfer->status !== TransferStatus::AWAITING_APPROVAL) {
            abort(403, 'Only transfers with status "Pending Pricing" or "Awaiting Approval" can be updated.');
        }
        $this->receiver_name = $this->transfer->receiver_name;
        $this->notes = $this->transfer->notes;
        $this->fee_mode = $this->transfer->fee_mode;
        $this->requested_currency = $this->transfer->requested_currency;
        $this->requested_amount = $this->transfer->requested_amount;
        $this->receiver_method = $this->transfer->receiver_method;
        $this->receiver_wallet_phone = $this->transfer->receiver_wallet_phone;
        $this->receiver_account_number = $this->transfer->receiver_account_number;
    }

    public function updateTransfer()
    {
        if ($this->transfer->status !== TransferStatus::PENDING_PRICING && $this->transfer->status !== TransferStatus::AWAITING_APPROVAL) {
            abort(403, 'Only transfers with status "Pending Pricing" or "Awaiting Approval" can be updated.');
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

        ]);

        $this->transfer->fill([
            'receiver_name' => $this->receiver_name,
            'notes' => $this->notes,
            'fee_mode' => $this->fee_mode,
            'requested_currency' => $this->requested_currency,
            'requested_amount' => $this->requested_amount,
            'receiver_method' => $this->receiver_method,
            'receiver_wallet_phone' => $this->receiver_wallet_phone,
            'receiver_account_number' => $this->receiver_account_number,
        ]);
        if ($this->transfer->status === TransferStatus::AWAITING_APPROVAL) {
            $this->transfer->exchange_rate = null;
            $this->transfer->commission_amount = null;
            $this->transfer->commission_currency = null;
            $this->transfer->bank_account_id = null;
            $this->transfer->priced_by = null;
            $this->transfer->priced_at = null;
            $this->transfer->status = TransferStatus::PENDING_PRICING;
        }
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
        @if($receiver_method === ReceiverMethod::BANK)
            <div>
                <label>Receiver Bank Account Number</label>
                <input type="text" wire:model="receiver_account_number">
                @error('receiver_account_number') <span>{{ $message }}</span> @enderror
            </div>
        @elseif($receiver_method === ReceiverMethod::WALLET)
            <div>
                <label>Receiver Wallet Number</label>
                <input type="text" wire:model="receiver_wallet_phone">
                @error('receiver_wallet_phone') <span>{{ $message }}</span> @enderror
            </div>
        @endif

        <div>
            <label>Fee Mode</label>
            <select wire:model="fee_mode">
                @foreach(FeeMode::cases() as $feeMode)
                    <option value="{{ $feeMode->value }}">{{ $feeMode->name }}</option>
                @endforeach
            </select>
            @error('fee_mode') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label>Requested Currency</label>
            <select wire:model="requested_currency">
                @foreach(CurrencyType::cases() as $currency)
                    <option value="{{ $currency->value }}">{{ $currency->name }}</option>
                @endforeach
            </select>
            @error('requested_currency') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label>Requested Amount</label>
            <input type="number" step="0.01" wire:model="requested_amount">
            @error('requested_amount') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label>Notes</label>
            <input type="text" wire:model="notes">
            @error('notes') <span>{{ $message }}</span> @enderror
        </div>

        <button type="submit">Update Transfer</button>
    </form>
</div>
