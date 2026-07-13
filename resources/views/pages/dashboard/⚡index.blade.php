<?php

use App\Enums\TransferStatus;
use App\Models\Transfer;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')]
class extends Component {

    #[Computed]
    public function transferStats()
    {
        return [
            'pending' => Transfer::where('status', TransferStatus::PENDING)->count(),
            'completed' => Transfer::where('status', TransferStatus::COMPLETED)->count(),
            'cancelled' => Transfer::where('status', TransferStatus::CANCELLED)->count(),
        ];
    }
    #[Computed]
    public function pendingTransfers()
    {
        return Transfer::where('status', TransferStatus::PENDING)
            ->latest()
            ->take(5)
            ->get();
    }

};
?>

<div>

    <h1>Dashboard</h1>

    <p>Welcome, {{ auth()->user()->name }}</p>

    <hr>

    <h3>Quick Actions</h3>

    <div>
        <a href="{{ route('create-transfer') }}">Create Transfer</a> |
        <a href="{{ route('transfers.index') }}">Transfers</a> |
        <a href="{{ route('bank-accounts.index') }}">Bank Accounts</a>
    </div>

    <br>

    <h3>Transfer Overview</h3>

    <table border="1" cellpadding="8">

        <thead>
        <tr>
            <th>Status</th>
            <th>Total</th>
        </tr>
        </thead>

        <tbody>

        <tr>
            <td>Pending Transfers</td>
            <td>{{ $this->transferStats['pending'] }}</td>
        </tr>


        <tr>
            <td>Completed</td>
            <td>{{ $this->transferStats['completed'] }}</td>
        </tr>

        <tr>
            <td>Cancelled</td>
            <td>{{ $this->transferStats['cancelled'] }}</td>
        </tr>

        </tbody>

    </table>

    <hr>

    <h3>Transfers Requiring Action</h3>

    @if($this->pendingTransfers->isNotEmpty())
        <h4>Pending Transfers</h4>

        <table border="1" cellpadding="8">
            <thead>
            <tr>
                <th>Reference</th>
                <th>Receiver</th>
                <th>Amount</th>
                <th></th>
            </tr>
            </thead>

            <tbody>
            @foreach($this->pendingTransfers as $transfer)
                <tr>
                    <td>{{ $transfer->reference_number }}</td>
                    <td>{{ $transfer->receiver_name }}</td>
                    <td>{{ $transfer->requested_amount }} {{ $transfer->requested_currency->name }}</td>
                    <td>
                        <a href="{{ route('transfers.show', $transfer) }}">Open</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <br>
    @endif

</div>
