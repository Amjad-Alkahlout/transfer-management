<?php

use App\Enums\CurrencyType;
use App\Enums\PaymentStatus;
use App\Enums\TransferStatus;
use App\Models\Transfer;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public Transfer $transfer;

    public $payment_amount;
    public $notes;

    public function mount(Transfer $transfer)
    {
        $this->transfer = $transfer;
    }

    #[Computed]
    public function payments()
    {
        return $this->transfer->payments()->latest()->get();
    }


    public function receivePayment()
    {
        if ($this->transfer->payment_status === PaymentStatus::PAID) {
            abort(403);
        }

        $this->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($this->transfer->status === TransferStatus::CANCELLED) {
            $this->addError('payment_amount', 'This transfer has been cancelled. No further payments can be received.');
            return;
        }

        if ($this->payment_amount > $this->transfer->remaining_amount) {
            $this->addError('payment_amount', 'Payment amount cannot exceed the remaining balance.');
            return;
        }

        DB::transaction(function () {
            $this->transfer->payments()->create([
                'amount' => $this->payment_amount,
                'currency' => $this->transfer->customer_payable_currency,
                'received_by' => auth()->id(),
                'received_at' => now(),
                'notes' => $this->notes,
            ]);

            $this->transfer->paid_amount += $this->payment_amount;
            $this->transfer->remaining_amount = $this->transfer->customer_payable_amount - $this->transfer->paid_amount;
            if (round($this->transfer->remaining_amount, 2) <= 0) {

                $this->transfer->payment_status = PaymentStatus::PAID;

            } else {

                $this->transfer->payment_status = PaymentStatus::PARTIALLY_PAID;
            }
            $this->transfer->save();
        });
        $this->reset([
            'payment_amount',
            'notes',
        ]);
        session()->flash('message', 'Payment received successfully.');
        return redirect()->route('transfers.show', $this->transfer);
    }
};
?>

<div>
    <div>
        <h2>Receive Payment for Transfer: {{ $this->transfer->reference_number }}</h2>
        <p>Requested
            Amount: {{ $this->transfer->customer_payable_amount }} {{ $this->transfer->customer_payable_currency->name }}</p>
        <p>Paid Amount: {{ $this->transfer->paid_amount }} {{ $this->transfer->customer_payable_currency->name }}</p>
        <p>Remaining
            Amount: {{ $this->transfer->remaining_amount }} {{ $this->transfer->customer_payable_currency->name }}</p>
    </div>
    <table>
        <thead>
        <tr>
            <th>Transfer Reference</th>
            <th>Paid Amount</th>
            <th>Received By</th>
            <th>Payment Date</th>
            <th>Notes</th>
        </tr>
        </thead>
        <tbody>
        @foreach($this->payments as $payment)
            <tr>
                <td>{{ $payment->transfer->reference_number }}</td>
                <td>{{ $payment->amount }} {{ $payment->currency->name }}</td>
                <td>{{ $payment->receiver->name }}</td>
                <td>{{ $payment->created_at->format('Y-m-d H:i:s') }}</td>
                <td>{{ $payment->notes }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>


    <div>
        <form wire:submit.prevent="receivePayment">
            <div>Payment Details</div>
            <label>Amount</label>
            <input type="number" step="0.01" wire:model="payment_amount">
            @error('payment_amount') <span class="error">{{ $message }}</span> @enderror

            <label>Notes</label>
            <textarea wire:model="notes"></textarea>
            @error('notes') <span class="error">{{ $message }}</span> @enderror

            <button type="submit">Add Payment</button>
            <a href="{{ route('transfers.show', $transfer) }}">Cancel</a>
        </form>

    </div>
</div>
