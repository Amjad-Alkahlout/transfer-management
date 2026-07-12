<?php

use App\Enums\CurrencyType;
use App\Enums\TransferStatus;
use App\Models\BankAccount;
use App\Models\Transfer;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;



new class extends Component {


    public Transfer $transfer;



    public function mount(Transfer $transfer)
    {
        $this->transfer = $transfer;
    }

};
?>

<div>
    <a href="{{ route('transfers.index') }}">← Back to Transfers</a>
    <h1>Transfer Details</h1>
        <div>
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
                    @if($transfer->status === TransferStatus::AWAITING_APPROVAL||$transfer->status===TransferStatus::APPROVED||$transfer->status===TransferStatus::COMPLETED)
                        <th>Exchange Rate</th>
                        <th>Commission Amount</th>
                        <th>Commission Currency</th>
                        <th>Source Bank Account</th>
                        <th>Status</th>
                    @endif
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
                    @if($transfer->status === TransferStatus::AWAITING_APPROVAL||$transfer->status === TransferStatus::APPROVED||$transfer->status === TransferStatus::COMPLETED)
                        <td>{{ $transfer->exchange_rate }}</td>
                        <td>{{ $transfer->commission_amount }}</td>
                        <td>{{ $transfer->commission_currency->name }}</td>
                        <td>{{ $transfer->account->label }} - {{ $transfer->account->account_number }}</td>
                        <td>{{ str($transfer->status->value)->replace('_', ' ')->title() }}</td>
                    @endif
                </tr>

                </tbody>
            </table>

            <div>
                <h3>Actions</h3>
                <div>
            @if($transfer->status === TransferStatus::PENDING_PRICING)

                <a href="{{ route('transfers.price', $transfer) }}">Price Transfer</a>

            @endif
                </div>
                <div>
            @if($transfer->status === TransferStatus::AWAITING_APPROVAL)

                    <a href="{{ route('transfers.approval', $transfer) }}" >Approve</a>
                    <a href="{{ route('transfers.approval', $transfer) }}"  >Reject</a>

            @endif
                </div>
                <div>

            @if($transfer->status === TransferStatus::APPROVED)

                    <a href="{{ route('transfers.execute', $transfer) }}">Execute Transfer</a>



            @endif
                </div>
                <div>
            @if($transfer->status === TransferStatus::PENDING_PRICING || $transfer->status === TransferStatus::AWAITING_APPROVAL)

                    <a href="{{ route('transfers.edit',  $transfer) }}">Edit</a>

            @endif
                </div>
            </div>

        </div>



</div>

