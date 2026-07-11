<?php

use App\Enums\FeeMode;
use App\Enums\TransferStatus;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new  #[Layout('layouts::app')]
class extends Component {
    public $sender_name;
    public $sender_phone;
    public $receiver_name;
    public $receiver_phone;
    public $notes;
    public $fee_mode;
    public $requested_currency;
    public $requested_amount;

    public function createTransfer()
    {
        $this->validate([
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'nullable|string|max:20',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:255',
            'fee_mode' => [
                'required',
                Rule::enum(FeeMode::class),
            ],
            'requested_currency' => 'required|string|max:3',
            'requested_amount' => 'required|numeric|min:0.01',
        ]);
        $transfer = new \App\Models\Transfer();
        $transfer->fill([
            'sender_name' => $this->sender_name,
            'sender_phone' => $this->sender_phone,
            'receiver_name' => $this->receiver_name,
            'receiver_phone' => $this->receiver_phone,
            'notes' => $this->notes,
            'fee_mode' => $this->fee_mode,
            'requested_currency' => $this->requested_currency,
            'requested_amount' => $this->requested_amount,

        ]);
        $transfer->reference_number = Str::upper('TRF-' . now()->format('Ymd') . '-' . Str::random(6));
        $transfer->created_by = auth()->id();
        $transfer->status = TransferStatus::PENDING_PRICING;
        $transfer->save();
        session()->flash('message', 'Transfer created successfully.');

        $this->reset(['sender_name', 'sender_phone', 'receiver_name', 'receiver_phone', 'notes', 'fee_mode', 'requested_currency', 'requested_amount']);
    }
};
?>

<div>
    <div>
        <h1>create Transfer</h1>
        <form wire:submit.prevent="createTransfer">
            <div>
                <label>Sender Name</label>
                <input type="text" wire:model="sender_name">
                @error('sender_name') <span>{{ $message }}</span> @enderror
            </div>

            <div>
                <label>Sender Phone</label>
                <input type="text" wire:model="sender_phone">
                @error('sender_phone') <span>{{ $message }}</span> @enderror
            </div>

            <div>
                <label>Receiver Name</label>
                <input type="text" wire:model="receiver_name">
                @error('receiver_name') <span>{{ $message }}</span> @enderror
            </div>

            <div>
                <label>Receiver Phone</label>
                <input type="text" wire:model="receiver_phone">
                @error('receiver_phone') <span>{{ $message }}</span> @enderror
            </div>

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
                <input type="text" wire:model="requested_currency">
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
