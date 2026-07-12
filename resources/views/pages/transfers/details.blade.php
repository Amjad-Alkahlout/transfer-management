<?php

use App\Enums\CurrencyType;
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
    public $exchange_rate;
    public $commission_amount;
    public $commission_currency;
    public $bank_account_id;
    public $transfer_proof_path;


    public function mount(Transfer $transfer)
    {
        $this->transfer = $transfer;
    }

    #[Computed]
    public function accounts()
    {
        return BankAccount::where('is_active', true)->get();
    }

    public function priceTransfer()
    {
        if ($this->transfer->status !== TransferStatus::PENDING_PRICING) {
            abort(403, 'This transfer cannot be priced in its current state.');
        }
        $this->validate([
            'exchange_rate' => 'required|numeric|min:0.01',
            'commission_amount' => 'required|numeric|min:0',
            'commission_currency' => [
                'required',
                Rule::enum(CurrencyType::class)
            ],
            'bank_account_id' => 'required|exists:bank_accounts,id',
        ]);
        $this->transfer->fill([
            'exchange_rate' => $this->exchange_rate,
            'commission_amount' => $this->commission_amount,
            'commission_currency' => $this->commission_currency,
            'bank_account_id' => $this->bank_account_id,
        ]);
        $this->transfer->status = TransferStatus::AWAITING_APPROVAL;
        $this->transfer->priced_by = auth()->id();
        $this->transfer->priced_at = now();
        $this->transfer->save();

        session()->flash('price_message', 'Transfer priced successfully.');
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
        $this->dispatch('approval-success');
    }

    public function cancelTransfer()
    {
        if ($this->transfer->status !== TransferStatus::AWAITING_APPROVAL) {
            abort(403, 'This transfer cannot be approved in its current state.');
        }
        $this->transfer->status = TransferStatus::CANCELLED;
        $this->transfer->cancelled_by = auth()->id();
        $this->transfer->cancelled_at = now();
        $this->transfer->save();

        session()->flash('cancel_message', 'Transfer cancelled successfully.');
        $this->dispatch('cancellation-success');
    }

    public function completeTransfer()
    {
        if ($this->transfer->status !== TransferStatus::APPROVED) {
            abort(403, 'This transfer cannot be completed in its current state.');
        }

        $this->validate([
            'transfer_proof_path' => 'required|file|mimes:jpg,jpeg,png,pdf|mimetypes:image/jpeg,image/png,application/pdf|max:2048',
        ]);

        $path = $this->transfer_proof_path->store('transfer_proofs', 'local');
        try {
            DB::transaction(function () use ($path) {
                $this->transfer->status = TransferStatus::COMPLETED;
                $this->transfer->completed_at = now();
                $this->transfer->comppleted_by = auth()->id();
                $this->transfer->transfer_proof_path = $path;
                $this->transfer->save();
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        session()->flash('complete_message', 'Transfer completed successfully.');
        $this->dispatch('completion-success');
        $this->reset('transfer_proof_path');
    }

};
?>

<div>
    <a href="{{ route('transfers.index') }}">Back to Transfers List</a>
    <h1>Transfer Review</h1>
    <div>
        @if(session()->has('price_message'))
            <div>
                {{ session('price_message') }}
            </div>
        @elseif(session()->has('approve_message'))
            <div>
                {{ session('approve_message') }}
            </div>
        @elseif(session()->has('cancel_message'))
            <div>
                {{ session('cancel_message') }}
            </div>
            @elseif(session()->has('complete_message'))
            <div>
                {{ session('complete_message') }}
            </div>
        @endif

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
                        @if($transfer->status === TransferStatus::AWAITING_APPROVAL)
                            <th>Approval/Rejection</th>
                        @endif
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
                        @if($transfer->status === TransferStatus::AWAITING_APPROVAL)
                            <td>
                                <button type="button" wire:click="approveTransfer">Approve</button>
                                <button type="button" wire:click="cancelTransfer">Cancel</button>
                            </td>
                        @endif

                    @endif
                </tr>

                </tbody>
            </table>
        </div>

        @if($transfer->status === TransferStatus::PENDING_PRICING)
            <div>
                <form wire:submit.prevent="priceTransfer">

                    <label for="exchange_rate">Exchange Rate</label>
                    <input type="number" id="exchange_rate" wire:model="exchange_rate" step="0.01">
                    @error('exchange_rate') <span>{{ $message }}</span> @enderror

                    <label for="commission_amount">Commission Amount</label>
                    <input type="number" id="commission_amount" wire:model="commission_amount" step="0.01">
                    @error('commission_amount') <span>{{ $message }}</span> @enderror

                    <label for="commission_currency">Commission Currency</label>
                    <select id="commission_currency" wire:model="commission_currency">
                        <option value="">Select Commission Currency</option>
                        @foreach(CurrencyType::cases() as $currency)
                            <option value="{{ $currency->value }}">{{ $currency->name }}</option>
                        @endforeach
                    </select>
                    @error('commission_currency') <span>{{ $message }}</span> @enderror

                    <label>source bank account</label>
                    <select wire:model="bank_account_id">
                        <option value="">Select Source Bank Account</option>
                        @foreach($this->accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->label }}
                                - {{ $account->account_number }}</option>
                        @endforeach
                    </select>
                    @error('bank_account_id') <span>{{ $message }}</span> @enderror
                    <button type="submit">Price Transfer</button>

                </form>
            </div>
        @endif

            @if($transfer->status === TransferStatus::APPROVED)
                <form wire:submit.prevent="completeTransfer">
                    <label>Upload Transfer Proof</label>
                    <input type="file" wire:model="transfer_proof_path">
                    @error('transfer_proof_path') <span>{{ $message }}</span> @enderror
                    <button type="submit">Complete Transfer</button>
                </form>
            @endif
    </div>
</div>

