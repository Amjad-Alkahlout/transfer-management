<?php

use App\Models\BankAccount;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')]
class extends Component {
    public $owner_name;
    public $label;
    public $bank_name;
    public $account_number;
    public $currency;
    public $notes;
    public $is_active = true;

    public function addBankAccount()
    {
        $this->validate([
            'owner_name' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'nullable|string|max:20',
            'currency' => 'required|string|max:3',
            'is_active' => 'required|boolean',
            'notes' => 'nullable|string|max:255',
        ]);

        BankAccount::create([
            'owner_name' => $this->owner_name,
            'label' => $this->label,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'currency' => $this->currency,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
        ]);
        $this->reset(['owner_name', 'label', 'bank_name', 'account_number', 'currency', 'notes']);
        unset($this->accounts);
        session()->flash('message', 'Bank account added successfully.');
    }
    public function toggleActiveStatus($id){

        $account = BankAccount::findOrFail($id);
            $account->is_active = !$account->is_active;
            $account->save();
            session()->flash('message', 'Bank account state changed successfully.');
            unset($this->accounts);

    }

    #[Computed]
    public function accounts()
    {
        return BankAccount::latest()->get();
    }

};
?>

<div>

    @if (session()->has('message'))
        <div>
            {{ session('message') }}
        </div>
    @endif

    <div>
        <div>
            <a href="{{ route('dashboard') }}">Back to Dashboard</a>
        </div>
        <div>
            Add Bank Account
        </div>
        <div>
            <form wire:submit="addBankAccount">
                <label>Owner Name</label>
                <input type="text" wire:model="owner_name" placeholder="Owner Name">
                @error('owner_name')
                <span>{{ $message }}</span>
                @enderror
                <label>Label</label>
                <input type="text" wire:model="label" placeholder="Label">
                @error('label') <span>{{ $message }}</span> @enderror
                <label>Bank Name</label>
                <input type="text" wire:model="bank_name" placeholder="Bank Name">
                @error('bank_name') <span>{{ $message }}</span> @enderror
                <label>Account Number</label>
                <input type="text" wire:model="account_number" placeholder="Account Number">
                @error('account_number') <span>{{ $message }}</span> @enderror
                <label>Currency</label>
                <input type="text" wire:model="currency" placeholder="Currency">
                @error('currency') <span>{{ $message }}</span> @enderror
                <label>Is Active</label>
                <input type="checkbox" wire:model="is_active">
                <label>Notes</label>
                <input type="text" wire:model="notes" placeholder="Notes">
                @error('notes') <span>{{ $message }}</span> @enderror
                <button type="submit">Add Bank Account</button>
            </form>
        </div>
    </div>

    <div>
        <div>
            Bank Accounts
        </div>
        <table>
            <thead>
            <tr>
                <th>Owner Name</th>
                <th>Label</th>
                <th>Bank Name</th>
                <th>Account Number</th>
                <th>Currency</th>
                <th>Is Active</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($this->accounts as $account)
                <tr>
                    <td>{{ $account->owner_name }}</td>
                    <td>{{ $account->label }}</td>
                    <td>{{ $account->bank_name }}</td>
                    <td>{{ $account->account_number }}</td>
                    <td>{{ $account->currency }}</td>
                    <td>{{ $account->is_active ? 'Yes' : 'No' }}</td>
                    <td>
                        <button wire:click="toggleActiveStatus({{ $account->id }})">
                            {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
