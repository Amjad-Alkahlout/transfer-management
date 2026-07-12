<?php

use App\Enums\TransferStatus;
use App\Models\Transfer;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;
    public Transfer $transfer;
    public $transfer_proof_path;

    public function mount(Transfer $transfer)
    {
        if ($transfer->status !== \App\Enums\TransferStatus::APPROVED) {
            abort(403, 'This transfer cannot be executed in its current state.');
        }
        $this->transfer = $transfer;
    }

    public function executeTransfer()
    {
        if ($this->transfer->status !== TransferStatus::APPROVED) {
            abort(403, 'This transfer cannot be executed in its current state.');
        }

        $this->validate([
            'transfer_proof_path' => 'required|file|mimes:jpg,jpeg,png,pdf|mimetypes:image/jpeg,image/png,application/pdf|max:2048',
        ]);

        $path = $this->transfer_proof_path->store('transfer_proofs', 'public');
        try {
            DB::transaction(function () use ($path) {
                $this->transfer->status = TransferStatus::COMPLETED;
                $this->transfer->completed_at = now();
                $this->transfer->completed_by = auth()->id();
                $this->transfer->transfer_proof_path = $path;
                $this->transfer->save();
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        session()->flash('complete_message', 'Transfer completed successfully.');
        return redirect()->route('transfers.show', $this->transfer);
    }
};
?>

<div>
    <div>Execute Transfer</div>
    <table>
        <thead>
        <tr>
            <th>Reference Number</th>
            <th>Receiver Name</th>
            <th>Receiver Method</th>
            @if($transfer->receiver_method === \App\Enums\ReceiverMethod::BANK)
                <th>Bank Account Number</th>
            @elseif($transfer->receiver_method === \App\Enums\ReceiverMethod::WALLET)
                <th>Wallet Number</th>
            @endif
            <th>Requested Amount and Currency</th>
            <th>Fee mode</th>
            <th>Commission Amount</th>
            <th>Commission Currency</th>
            <th>Exchange Rate</th>
            <th>Source Bank Account</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{{ $transfer->reference_number }}</td>
            <td>{{ $transfer->receiver_name }}</td>
            <td>{{ $transfer->receiver_method->name }}</td>
            @if($transfer->receiver_method === \App\Enums\ReceiverMethod::BANK)
                <td>{{ $transfer->receiver_account_number }}</td>
            @elseif($transfer->receiver_method === \App\Enums\ReceiverMethod::WALLET)
                <td>{{ $transfer->receiver_wallet_phone }}</td>
            @endif
            <td>{{ $transfer->requested_amount }} {{ $transfer->requested_currency->name }}</td>
            <td>{{ $transfer->fee_mode->name }}</td>
            <td>{{ $transfer->commission_amount }}</td>
            <td>{{ $transfer->commission_currency->name }}</td>
            <td>{{ $transfer->exchange_rate }}</td>
            <td> {{$transfer->account->label}} - {{$transfer->account->account_number}}</td>
            <td>{{ $transfer->status->name }}</td>
        </tr>
        </tbody>
    </table>
    <form wire:submit="executeTransfer">
        <div>
            <label for="transfer_proof_path">Upload Transfer Proof</label>
            <input type="file" id="transfer_proof_path" wire:model="transfer_proof_path" />
            @error('transfer_proof_path')
                <span>{{ $message }}</span>
            @enderror
        </div>
        <button  type="submit" >Execute Transfer</button>
    </form>
</div>
