<?php

use App\Enums\PaymentStatus;
use App\Enums\TransferStatus;
use App\Models\CommissionRule;
use App\Models\ExchangeRate;
use App\Models\Transfer;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')]
class extends Component
{
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
    public function paymentStats()
    {
        return [
            'unpaid' => Transfer::where('payment_status', PaymentStatus::UNPAID)->count(),
            'partially_paid' => Transfer::where('payment_status', PaymentStatus::PARTIALLY_PAID)->count(),
            'paid' => Transfer::where('payment_status', PaymentStatus::PAID)->count(),
        ];
    }

    #[Computed]
    public function exchangeRates()
    {
        return ExchangeRate::orderBy('currency')->get();
    }

    #[Computed]
    public function commissionStats()
    {
        return CommissionRule::selectRaw('currency, COUNT(*) as total')
            ->groupBy('currency')
            ->get();
    }
};
?>

<div>

    <h1>Dashboard</h1>

    <p>Welcome, {{ auth()->user()->name }}</p>

    <hr>

    <h2>Quick Actions</h2>

    <div>
        <a href="{{ route('transfers.create') }}">Create Transfer</a> |
        <a href="{{ route('transfers.index') }}">Transfers</a> |
        <a href="{{ route('bank-accounts.index') }}">Bank Accounts</a> |
        <a href="{{ route('exchange-rates.index') }}">Exchange Rates</a> |
        <a href="{{ route('commission-rules.index') }}">Commission Rules</a>
    </div>

    <br>

    <h2>Transfer Overview</h2>

    <table border="1" cellpadding="8">
        <thead>
        <tr>
            <th>Status</th>
            <th>Total</th>
        </tr>
        </thead>

        <tbody>

        <tr>
            <td>Pending</td>
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

    <br>

    <h2>Payment Overview</h2>

    <table border="1" cellpadding="8">

        <thead>
        <tr>
            <th>Status</th>
            <th>Total</th>
        </tr>
        </thead>

        <tbody>

        <tr>
            <td>Unpaid</td>
            <td>{{ $this->paymentStats['unpaid'] }}</td>
        </tr>

        <tr>
            <td>Partially Paid</td>
            <td>{{ $this->paymentStats['partially_paid'] }}</td>
        </tr>

        <tr>
            <td>Paid</td>
            <td>{{ $this->paymentStats['paid'] }}</td>
        </tr>

        </tbody>

    </table>

    <br>

    <h2>Exchange Rates</h2>

    <table border="1" cellpadding="8">

        <thead>
        <tr>
            <th>Currency</th>
            <th>Rate to USD</th>
        </tr>
        </thead>

        <tbody>

        @foreach($this->exchangeRates as $rate)
            <tr>
                <td>{{ strtoupper($rate->currency->value) }}</td>
                <td>{{ $rate->rate_to_usd }}</td>
            </tr>
        @endforeach

        </tbody>

    </table>

    <br>

    <a href="{{ route('exchange-rates.index') }}">
        Manage Exchange Rates
    </a>

    <br><br>

    <h2>Commission Rules</h2>

    <table border="1" cellpadding="8">

        <thead>
        <tr>
            <th>Currency</th>
            <th>Total Rules</th>
        </tr>
        </thead>

        <tbody>

         @foreach($this->commissionStats as $rule)
            <tr>
                <td>{{ $rule->currency->name }}</td>
                <td>{{ $rule->total }}</td>
            </tr>
         @endforeach

        </tbody>

    </table>

    <br>

    <a href="{{ route('commission-rules.index') }}">
        Manage Commission Rules
    </a>

</div>
