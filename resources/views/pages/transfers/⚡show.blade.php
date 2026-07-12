<?php

use App\Enums\CurrencyType;
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
    public $exchange_rate;
    public $commission_amount;
    public $commission_currency;
    public $bank_account_id;
    public $transfer_proof_path;
    public $transfer_proof_form=false;
    public bool $showPricingForm = false;


    public function mount(Transfer $transfer)
    {
        $this->transfer = $transfer;
        $this->exchange_rate = $transfer->exchange_rate;
        $this->commission_amount = $transfer->commission_amount;
        $this->commission_currency = $transfer->commission_currency;
        $this->bank_account_id = $transfer->bank_account_id;
    }
    public function openTransferProofForm()
    {
        $this->transfer_proof_form = true;
    }
    public function hideTransferProofForm()
    {
        $this->transfer_proof_form = false;
    }
    public function openPricingForm()
    {
        $this->showPricingForm = true;
    }

    public function hidePricingForm()
    {
        $this->showPricingForm = false;
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
        $this->showPricingForm = false;
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
          $this->transfer_proof_form=false;
        session()->flash('complete_message', 'Transfer completed successfully.');
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
                <th>Requested Amount and Currency</th>
                <th>Fee mode</th>
                <th>Status</th>
                @if($transfer->status === TransferStatus::AWAITING_APPROVAL
                    ||$transfer->status===TransferStatus::APPROVED
                    ||$transfer->status===TransferStatus::COMPLETED
                    )
                    <th>Exchange Rate</th>
                    <th>Commission Amount</th>
                    <th>Commission Currency</th>
                    <th>Source Bank Account</th>

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
                <td>{{ $transfer->fee_mode->name }}</td>
                <td>{{ str($transfer->status->value)->replace('_', ' ')->title() }}</td>
                @if($transfer->status === TransferStatus::AWAITING_APPROVAL||$transfer->status === TransferStatus::APPROVED||$transfer->status === TransferStatus::COMPLETED)
                    <td>{{ $transfer->exchange_rate }}</td>
                    <td>{{ $transfer->commission_amount }}</td>
                    <td>{{ $transfer->commission_currency->name }}</td>
                    <td>{{ $transfer->account->label }} - {{ $transfer->account->account_number }}</td>
                @endif
            </tr>

            </tbody>
        </table>

        <div>
            <h3>Actions</h3>

                @if($transfer->status === TransferStatus::PENDING_PRICING)

                    <button wire:click="openPricingForm">Price Transfer</button>

                @endif


                @if($transfer->status === TransferStatus::AWAITING_APPROVAL)

                    <button wire:click="approveTransfer">Approve</button>
                    <button wire:click="cancelTransfer">Cancel</button>

                @endif


                @if($transfer->status === TransferStatus::APPROVED)

                    <button wire:click="openTransferProofForm">Execute Transfer</button>

                @endif


                @if($transfer->status === TransferStatus::PENDING_PRICING || $transfer->status === TransferStatus::AWAITING_APPROVAL)

                    <a href="{{ route('transfers.edit',  $transfer) }}">Edit</a>

                @endif
        </div>


    </div>

    @if($showPricingForm)
        <div>
            <form wire:submit.prevent="priceTransfer">
                <div>
                    <label for="exchange_rate">Exchange Rate</label>
                    <input type="number" id="exchange_rate" wire:model="exchange_rate" step="0.01"/>
                    @error('exchange_rate')
                    <span>{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="commission_amount">Commission Amount</label>
                    <input type="number" id="commission_amount" wire:model="commission_amount" step="0.01"/>
                    @error('commission_amount')
                    <span>{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="commission_currency">Commission Currency</label>
                    <select id="commission_currency" wire:model="commission_currency">
                        <option value="">Select a Currency</option>
                        @foreach(CurrencyType::cases() as $currency)
                            <option value="{{ $currency->value }}">{{ $currency->name }}</option>
                        @endforeach
                    </select>
                    @error('commission_currency')
                    <span>{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="bank_account_id">Bank Account</label>
                    <select id="bank_account_id" wire:model="bank_account_id">
                        <option value="">Select a Bank Account</option>
                        @foreach($this->accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->label }}</option>
                        @endforeach
                    </select>
                    @error('bank_account_id')
                    <span>{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit">Save</button>
                <button type="button" wire:click="hidePricingForm">Cancel</button>
            </form>
        </div>
    @endif

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

