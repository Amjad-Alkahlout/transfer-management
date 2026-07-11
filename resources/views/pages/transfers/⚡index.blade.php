<?php

use App\Models\Transfer;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function transfers()
    {
        return Transfer::latest()->paginate(15);
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
                <th>Requested Amount</th>
                <th>Requested Currency</th>
                <th>Status</th>
                <th>Created By</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($this->transfers as $transfer)
                <tr>
                    <td>{{ $transfer->reference_number }}</td>
                    <td>{{ $transfer->sender_name }}</td>
                    <td>{{ $transfer->receiver_name }}</td>
                    <td>{{ $transfer->requested_amount }} {{ $transfer->requested_currency }}</td>
                    <td>{{ str($transfer->status->value)->replace('_', ' ')->title() }}</td>
                    <td>{{ $transfer->creator?->name }}</td>
                    <td>{{ $transfer->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $this->transfers->links() }}
</div>
