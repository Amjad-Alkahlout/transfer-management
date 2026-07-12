<?php

use App\Models\Transfer;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function transfers()
    {
        return Transfer::with('creator')->latest()->paginate(15);
    }
};
?>

<div>
    <h1>Transfers</h1>
    <a href="{{ route('create-transfer') }}">Create Transfer</a>
    <table>
        <thead>
            <tr>
                <th>Reference Number</th>
                <th>Sender Name</th>
                <th>Receiver Name</th>
                <th>Receiver Method</th>
                <th>Requested Amount</th>
                <th>Requested Currency</th>
                <th>Status</th>
                <th>Created By</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($this->transfers as $transfer)
                <tr>
                    <td>{{ $transfer->reference_number }}</td>
                    <td>{{ $transfer->sender_name }}</td>
                    <td>{{ $transfer->receiver_name }}</td>
                    @if($transfer->receiver_method === \App\Enums\ReceiverMethod::BANK)
                        <td>Bank Account: {{ $transfer->receiver_account_number }}</td>
                    @elseif($transfer->receiver_method === \App\Enums\ReceiverMethod::WALLET)
                        <td>Wallet Number: {{ $transfer->receiver_wallet_phone }}</td>
                    @endif
                    <td>{{ $transfer->requested_amount }} {{ $transfer->requested_currency->name }}</td>
                    <td>{{ str($transfer->status->value)->replace('_', ' ')->title() }}</td>
                    <td>{{ $transfer->creator?->name }}</td>
                    <td>{{ $transfer->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('transfers.details', ['transfer' => $transfer]) }}">View</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $this->transfers->links() }}
</div>
