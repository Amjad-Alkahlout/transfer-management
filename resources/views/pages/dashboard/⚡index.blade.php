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
            'pending_pricing' => Transfer::where('status', TransferStatus::PENDING_PRICING)->count(),
            'awaiting_approval' => Transfer::where('status', TransferStatus::AWAITING_APPROVAL)->count(),
            'approved' => Transfer::where('status', TransferStatus::APPROVED)->count(),
            'completed' => Transfer::where('status', TransferStatus::COMPLETED)->count(),
            'cancelled' => Transfer::where('status', TransferStatus::CANCELLED)->count(),
        ];
    }
    #[Computed]
    public function pendingPricingTransfers()
    {
        return Transfer::where('status', TransferStatus::PENDING_PRICING)
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function awaitingApprovalTransfers()
    {
        return Transfer::where('status', TransferStatus::AWAITING_APPROVAL)
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function approvedTransfers()
    {
        return Transfer::where('status', TransferStatus::APPROVED)
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
            <td>Pending Pricing</td>
            <td>{{ $this->transferStats['pending_pricing'] }}</td>
        </tr>

        <tr>
            <td>Awaiting Approval</td>
            <td>{{ $this->transferStats['awaiting_approval'] }}</td>
        </tr>

        <tr>
            <td>Approved</td>
            <td>{{ $this->transferStats['approved'] }}</td>
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

    @if($this->pendingPricingTransfers->isNotEmpty())
        <h4>Pending Pricing</h4>

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
            @foreach($this->pendingPricingTransfers as $transfer)
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


    @if($this->awaitingApprovalTransfers->isNotEmpty())
        <h4>Awaiting Approval</h4>

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
            @foreach($this->awaitingApprovalTransfers as $transfer)
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


    @if($this->approvedTransfers->isNotEmpty())
        <h4>Ready To Execute</h4>

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
            @foreach($this->approvedTransfers as $transfer)
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
    @endif

</div>
