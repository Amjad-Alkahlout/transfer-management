<?php

use App\Enums\TransferStatus;
use App\Models\Transfer;
use Livewire\Component;

new class extends Component {
    public Transfer $transfer;

    public function mount(Transfer $transfer)
    {
        if ($transfer->status !== \App\Enums\TransferStatus::AWAITING_APPROVAL) {
            abort(403, 'This transfer cannot be approved in its current state.');
        }
        $this->transfer = $transfer;
    }

    public function approveTransfer()
    {

        if ($this->transfer->status !== TransferStatus::AWAITING_APPROVAL) {
            abort(403, 'This transfer cannot be approved in its current state.');
        }
        $this->transfer->status = TransferStatus::APPROVED;
        $this->transfer->approved_at = now();
        $this->transfer->save();

        session()->flash('approve_message', 'Transfer approved successfully.');
        return redirect()->route('transfers.show', $this->transfer);
    }

    public function cancelTransfer()
    {
        if ($this->transfer->status !== TransferStatus::AWAITING_APPROVAL) {
            abort(403, 'This transfer cannot be cancelled in its current state.');
        }
        $this->transfer->status = TransferStatus::CANCELLED;
        $this->transfer->cancelled_by = auth()->id();
        $this->transfer->cancelled_at = now();
        $this->transfer->save();

        session()->flash('cancel_message', 'Transfer cancelled successfully.');
        return redirect()->route('transfers.show', $this->transfer);

    }
};
?>

<div>
    <div>Approve Transfer</div>
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
    <div>
        <button wire:click="approveTransfer">Approve</button>
        <button wire:click="cancelTransfer">Reject</button>
    </div>
</div>
