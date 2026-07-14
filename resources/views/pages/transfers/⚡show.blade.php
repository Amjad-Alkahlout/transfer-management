<?php

use App\Enums\CurrencyType;
use App\Enums\PaymentStatus;
use App\Enums\ReceiverMethod;
use App\Enums\TransferStatus;
use App\Models\BankAccount;
use App\Models\Transfer;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


new class extends Component {

    use WithFileUploads;

    public Transfer $transfer;
    public $transfer_proof_path;
    public $transfer_proof_form = false;


    public function mount(Transfer $transfer)
    {
        $this->transfer = $transfer;
    }

    public function openTransferProofForm()
    {
        $this->transfer_proof_form = true;
    }

    public function hideTransferProofForm()
    {
        $this->transfer_proof_form = false;
    }


    public function executeTransfer()
    {
        if ($this->transfer->status !== TransferStatus::PENDING) {
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
        $this->transfer_proof_form = false;
        session()->flash('complete_message', 'Transfer completed successfully.');
    }

    public function cancelTransfer()
    {
        if ($this->transfer->status !== TransferStatus::PENDING) {
            abort(403, 'This transfer cannot be cancelled in its current state.');
        }
        $this->transfer->status = TransferStatus::CANCELLED;
        $this->transfer->cancelled_by = auth()->id();
        $this->transfer->cancelled_at = now();
        $this->transfer->save();

        session()->flash('cancel_message', 'Transfer cancelled successfully.');

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
                @if($transfer->receiver_method === ReceiverMethod::BANK)
                    <th>Bank Account Number</th>
                @elseif($transfer->receiver_method === ReceiverMethod::WALLET)
                    <th>Wallet Number</th>
                @endif
                <th>Customer Requested</th>
                <th>Transfer Amount</th>
                <th>Commission</th>
                <th>Fee mode</th>
                <th>Customer Pays</th>
                <th>Payment Status</th>
                <th>Paid Amount</th>
                <th>Remaining Amount</th>
                <th>Transfer Status</th>
                @if($transfer->status === TransferStatus::COMPLETED && $transfer->transfer_proof_path)
                    <th>Transfer Proof</th>
                @endif

            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{{ $transfer->reference_number }}</td>
                <td>{{ $transfer->receiver_name }}</td>
                <td>{{ $transfer->receiver_method->name }}</td>
                @if($transfer->receiver_method === ReceiverMethod::BANK)
                    <td>{{ $transfer->receiver_account_number }}</td>
                @elseif($transfer->receiver_method === ReceiverMethod::WALLET)
                    <td>{{ $transfer->receiver_wallet_phone }}</td>
                @endif
                <td>{{ $transfer->requested_amount }} {{ $transfer->requested_currency->name }}</td>
                <td>{{ $transfer->transfer_amount }} {{ $transfer->requested_currency->name }}</td>
                <td>{{ $transfer->commission_amount }} {{ $transfer->commission_currency->name }}</td>
                <td>{{ $transfer->fee_mode->name }}</td>
                <td>{{ $transfer->customer_payable_amount }} {{ $transfer->customer_payable_currency->name }}</td>
                <td>{{ str($transfer->payment_status->value)->replace('_', ' ')->title() }}</td>
                <td>{{ $transfer->paid_amount }} {{ $transfer->customer_payable_currency->name }}</td>
                <td>{{ $transfer->remaining_amount }} {{ $transfer->customer_payable_currency->name }}</td>
                <td>{{ str($transfer->status->value)->replace('_', ' ')->title() }}</td>
                @if($transfer->status === TransferStatus::COMPLETED && $transfer->transfer_proof_path)
                    <td><a href="{{ Storage::url($transfer->transfer_proof_path) }}" target="_blank">View Proof</a></td>
                @endif
            </tr>

            </tbody>
        </table>

        <div>
            <h3>Actions</h3>
            @if($transfer->payment_status !== PaymentStatus::PAID && $transfer->status !== TransferStatus::CANCELLED)
                <a href="{{ route('transfers.receive-payment',  $transfer) }}">Receive Payment</a>
            @endif
            @if($transfer->status === TransferStatus::PENDING)
                @if($transfer->payment_status == PaymentStatus::UNPAID)
                <a href="{{ route('transfers.edit',  $transfer) }}">Edit</a>
                @endif
                <button wire:click="openTransferProofForm">Execute Transfer</button>
                <button wire:click="cancelTransfer">Cancel</button>
            @endif

        </div>


    </div>


    @if($transfer_proof_form)
        <div>
            <form wire:submit.prevent="executeTransfer">
                <div>
                    <label for="transfer_proof_path">Upload Transfer Proof</label>
                    <input type="file" id="transfer_proof_path" wire:model="transfer_proof_path"/>
                    @error('transfer_proof_path')
                    <span>{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit">Execute Transfer</button>
                <button type="button" wire:click="hideTransferProofForm">Cancel</button>
            </form>
        </div>
    @endif
</div>

