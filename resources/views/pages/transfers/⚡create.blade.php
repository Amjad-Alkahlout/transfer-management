<?php

use App\Enums\FeeMode;
use App\Enums\CurrencyType;
use App\Enums\ReceiverMethod;
use App\Enums\TransferStatus;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

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

    public function updatedReceiverMethod($value)
    {
        if ($value === ReceiverMethod::BANK->value) {
            $this->receiver_wallet_phone = null;
        } elseif ($value === ReceiverMethod::WALLET->value) {
            $this->receiver_account_number = null;
        }
    }

    public function createTransfer()
    {

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
        ]);
        $transfer = new \App\Models\Transfer();
        $transfer->fill([
            'receiver_name' => $this->receiver_name,
            'receiver_method' => $this->receiver_method,
            'notes' => $this->notes,
            'fee_mode' => $this->fee_mode,
            'requested_currency' => $this->requested_currency,
            'requested_amount' => $this->requested_amount,
            'receiver_wallet_phone' => $this->receiver_wallet_phone,
            'receiver_account_number' => $this->receiver_account_number,
        ]);
        $transfer->reference_number = Str::upper('TRF-' . now()->format('Ymd') . '-' . Str::random(6));
        $transfer->created_by = auth()->id();
        $transfer->status = TransferStatus::PENDING_PRICING;
        $transfer->save();
        session()->flash('message', 'Transfer created successfully.');
        $this->reset(['receiver_name', 'receiver_method', 'notes', 'fee_mode', 'requested_currency', 'requested_amount', 'receiver_wallet_phone', 'receiver_account_number']);
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

                    @if($receiver_method === \App\Enums\ReceiverMethod::BANK->value)
                        <div>
                            <label>Receiver Bank Account Number</label>
                            <input type="text" wire:model="receiver_account_number">
                            @error('receiver_account_number') <span>{{ $message }}</span> @enderror
                        </div>
                    @endif
                    @if($receiver_method === \App\Enums\ReceiverMethod::WALLET->value)
                        <div>
                            <label>Receiver Wallet Number</label>
                            <input type="text" wire:model="receiver_wallet_phone">
                            @error('receiver_wallet_phone') <span>{{ $message }}</span> @enderror
                        </div>
                    @endif


                    <div>
                        <label>Notes</label>
                        <textarea wire:model="notes"></textarea>
                        @error('notes') <span>{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label>Fee Mode</label>
                        <select wire:model="fee_mode">
                            <option value="">Select Fee Mode</option>
                            @foreach(FeeMode::cases() as $mode)
                                <option value="{{ $mode->value }}">{{ $mode->name }}</option>
                            @endforeach
                        </select>
                        @error('fee_mode') <span>{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label>Requested Currency</label>
                        <select wire:model="requested_currency">
                            <option value="">Select Requested Currency</option>
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

                    <button type="submit">Create Transfer</button>

                </form>
    </div>
</div>
