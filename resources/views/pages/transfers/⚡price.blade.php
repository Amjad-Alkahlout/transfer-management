<?php

use App\Enums\CurrencyType;
use App\Enums\ReceiverMethod;
use App\Enums\TransferStatus;
use App\Models\BankAccount;
use App\Models\Transfer;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {

    public Transfer $transfer;
    public $exchange_rate;
    public $commission_amount;
    public $commission_currency;
    public $bank_account_id;

    public function mount(Transfer $transfer)
    {
        $this->transfer = $transfer;
        if ($transfer->status !== TransferStatus::PENDING_PRICING) {
            abort(403);
        }
        $this->exchange_rate = $transfer->exchange_rate;
        $this->commission_amount = $transfer->commission_amount;
        $this->commission_currency = $transfer->commission_currency;
        $this->bank_account_id = $transfer->bank_account_id;
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
        return redirect()->route('transfers.show', $this->transfer);
    }
};
?>

<div>
    <div>Price Transfer</div>
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
            </tr>
            </tbody>
        </table>
    </div>
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
        <button type="submit">Price Transfer</button>
    </form>
</div>
