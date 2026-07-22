<?php

use App\Enums\CapitalAccountType;
use App\Enums\CapitalTransactionType;
use App\Enums\CurrencyType;
use App\Models\CapitalAccount;
use App\Models\CapitalTransaction;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Enums\Branch;
use App\Enums\TransactionDirection;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts::app')]
class extends Component {
    public $name;
    public $branch;
    public $currency;
    public $opening_balance;
    public $notes;
    public $account_type;
    public $is_active = true;
    public ?CapitalAccount $editingAccount = null;
    public $showAddAccountForm = false;
    public $showEditAccountForm = false;
    public CapitalAccount $selectedAccount;

    public bool $withdrawProfitForm = false;

    public float $withdrawAmount = 0;

    public ?string $withdrawNotes = null;

    public function mount()
    {
        Gate::authorize('view-capital-accounts');
    }

    public function openWithdrawProfitForm(int $accountId)
    {
        Gate::authorize('manage-capital-accounts');
        $this->selectedAccount = CapitalAccount::findOrFail($accountId);

        $this->withdrawProfitForm = true;
    }

    public function closeWithdrawProfitForm()
    {
        $this->reset([
            'withdrawAmount',
            'withdrawNotes',
        ]);

        $this->withdrawProfitForm = false;
    }

    public function openAddAccountForm()
    {
        Gate::authorize('manage-capital-accounts');
        $this->resetValidation();
        $this->showAddAccountForm = true;
    }

    public function closeAddAccountForm()
    {
        $this->reset(['name', 'branch', 'currency', 'opening_balance', 'notes', 'account_type']);
        $this->resetValidation();
        $this->is_active = true;
        $this->showAddAccountForm = false;
    }

    public function openEditAccountForm($id)
    {
        Gate::authorize('manage-capital-accounts');
        $account = CapitalAccount::findOrFail($id);
        $this->resetValidation();
        $this->showEditAccountForm = true;
        $this->name = $account->name;
        $this->notes = $account->notes;
        $this->editingAccount = $account;
    }

    public function closeEditAccountForm()
    {
        $this->reset(['name', 'branch', 'currency', 'notes', 'editingAccount']);
        $this->resetValidation();
        $this->showEditAccountForm = false;

    }

    public function withdrawProfit()
    {
        Gate::authorize('manage-capital-accounts');
        $this->validate([
            'withdrawAmount' => 'required|numeric|min:0.01|max:' . $this->selectedAccount->balance,
            'withdrawNotes' => 'nullable|string|max:255',
        ]);
        try {

            app(\App\Services\ProfitWithdrawalService::class)->withdraw(
                $this->selectedAccount,
                $this->withdrawAmount,
                $this->withdrawNotes,
            );

        } catch (\Throwable $e) {

            $this->addError('withdrawAmount', $e->getMessage());

            return;
        }
        session()->flash('success', 'Profit withdrawn successfully.');
        $this->closeWithdrawProfitForm();
        unset($this->accounts);
    }

    public function addCapitalAccount()
    {
        Gate::authorize('manage-capital-accounts');
        $this->validate([
            'name' => 'required|string|max:255',
            'branch' => [
                'required',
                Rule::enum(Branch::class)
            ],
            'currency' => [
                'required',
                Rule::enum(CurrencyType::class)
            ],
            'account_type' => [
                'required',
                Rule::enum(CapitalAccountType::class)
            ],
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);
        if (
            $this->account_type === CapitalAccountType::PROFIT &&
            $this->branch !== Branch::GAZA
        ) {
            $this->addError(
                'account_type',
                'Profit accounts can only be created for the Gaza branch.'
            );

            return;
        }
        $this->resetErrorBag('currency');

        $exists = CapitalAccount::where('branch', $this->branch)
            ->where('currency', $this->currency)
            ->where('account_type', $this->account_type)
            ->exists();

        if ($exists) {
            $this->addError(
                'currency',
                'An account with the same branch, currency, and account type already exists.'
            );

            return;
        }

        DB::transaction(function () {
            $account = CapitalAccount::create([
                'name' => $this->name,
                'branch' => $this->branch,
                'currency' => $this->currency,
                'balance' => $this->opening_balance,
                'is_active' => $this->is_active,
                'notes' => $this->notes,
                'account_type' => $this->account_type,
            ]);
            if ($this->opening_balance > 0) {
                CapitalTransaction::create([
                    'capital_account_id' => $account->id,

                    'amount' => $this->opening_balance,

                    'direction' => TransactionDirection::IN,

                    'transaction_type' => CapitalTransactionType::OPENING_BALANCE,

                    'balance_before' => 0,

                    'balance_after' => $this->opening_balance,

                    'notes' => 'Initial opening balance',

                    'created_by' => auth()->id(),
                ]);
            }
        });
        $this->closeAddAccountForm();
        unset($this->accounts);
        session()->flash('success', 'Capital account added successfully.');
    }


    public function toggleActiveStatus($id)
    {
        Gate::authorize('manage-capital-accounts');

        $account = CapitalAccount::findOrFail($id);
        $account->is_active = !$account->is_active;
        $account->save();
        $message = $account->is_active
            ? 'Capital account activated successfully.'
            : 'Capital account deactivated successfully.';

        session()->flash('success', $message);
        unset($this->accounts);

    }

    public function editCapitalAccount()
    {
        Gate::authorize('manage-capital-accounts');
        $this->validate([
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string|max:255',
        ]);

        $this->editingAccount->update([
            'name' => $this->name,
            'notes' => $this->notes,
        ]);
        $this->closeEditAccountForm();
        unset($this->accounts);
        session()->flash('success', 'Capital account updated successfully.');
    }


    #[Computed]
    public function accounts()
    {
        return CapitalAccount::latest()->get();
    }

};
?>

<div>

    <div>

        <x-ui.page-header
            title="Capital Accounts"
            description="Manage company capital accounts."
        >

            <x-slot:actions>
               @can('manage-capital-accounts')
                <x-ui.button
                    wire:click="openAddAccountForm"
                >
                    Add Capital Account
                </x-ui.button>
                @endcan

            </x-slot:actions>

        </x-ui.page-header>



        <div class="mb-6">

            <x-ui.button
                :href="route('dashboard')"
                variant="secondary"
            >
                ← Dashboard
            </x-ui.button>

        </div>
        <x-ui.card
            title="Capital Accounts"
            description="All configured capital accounts."
        >

            <x-ui.table>
                <x-ui.table-header>
                    <x-ui.table-row>
                <x-ui.table-head>Name</x-ui.table-head>
                    <x-ui.table-head>Branch</x-ui.table-head>
                <x-ui.table-head>Currency</x-ui.table-head>
                <x-ui.table-head>Current Balance</x-ui.table-head>
                <x-ui.table-head>Account Type</x-ui.table-head>
                <x-ui.table-head>Is Active</x-ui.table-head>
                <x-ui.table-head>Actions</x-ui.table-head>
              </x-ui.table-row>
              </x-ui.table-header>
                <x-ui.table-body>
                @foreach ($this->accounts as $account)
                        <x-ui.table-row>
                            <x-ui.table-cell>{{ $account->name }}</x-ui.table-cell>
                            <x-ui.table-cell>{{ $account->branch->name }}</x-ui.table-cell>
                            <x-ui.table-cell>{{ $account->currency->symbol() }}</x-ui.table-cell>
                            <x-ui.table-cell>{{ number_format($account->balance,2) }}
                                {{ $account->currency->symbol() }}</x-ui.table-cell>
                            <x-ui.table-cell>{{ str($account->account_type->value)->replace('_',' ')->title() }}</x-ui.table-cell>
                            <x-ui.table-cell>

                                @if($account->is_active)

                                    <x-ui.badge color="green">
                                        Active
                                    </x-ui.badge>

                                @else

                                    <x-ui.badge color="red">
                                        Inactive
                                    </x-ui.badge>

                                @endif

                            </x-ui.table-cell>
                            <x-ui.table-cell>

                                <div class="flex flex-wrap gap-2">

                                    @can('manage-capital-accounts')
                                        <x-ui.button
                                            variant="secondary"
                                            wire:click="toggleActiveStatus({{ $account->id }})"
                                            class="justify-center"
                                        >
                                            @if($account->is_active)
                                                Deactivate
                                            @else
                                                Activate
                                            @endif
                                        </x-ui.button>


                                        <x-ui.button
                                            variant="secondary"
                                            wire:click="openEditAccountForm({{ $account->id }})"
                                            class="justify-center"
                                        >
                                            Edit
                                        </x-ui.button>


                                        @if($account->account_type === \App\Enums\CapitalAccountType::PROFIT&& $account->balance > 0)
                                            <x-ui.button
                                                variant="success"
                                                wire:click="openWithdrawProfitForm({{ $account->id }})"
                                                class="justify-center"
                                            >
                                                Withdraw Profit
                                            </x-ui.button>
                                        @endif
                                    @endcan

                                </div>
                            </x-ui.table-cell>
                        </x-ui.table-row>
                      @endforeach
                   </x-ui.table-body>
            </x-ui.table>

        </x-ui.card>
    </div>

@can('manage-capital-accounts')
    @if($withdrawProfitForm)

        <x-ui.card
            title="Withdraw Profit"
            :description="'Withdraw profit from '.$selectedAccount->name"
            class="mt-6"
        >

            <form
                wire:submit.prevent="withdrawProfit"
                class="space-y-6"
            >

                <x-ui.input
                    label="Amount"
                    name="withdrawAmount"
                    type="number"
                    step="0.01"
                    wire:model="withdrawAmount"
                />

                <x-ui.textarea
                    label="Notes"
                    name="withdrawNotes"
                    rows="3"
                    wire:model="withdrawNotes"
                />
                <div class="flex justify-end gap-3">

                    <x-ui.button
                        type="button"
                        variant="secondary"
                        wire:click="closeWithdrawProfitForm"
                    >
                        Cancel
                    </x-ui.button>

                    <x-ui.button
                        type="submit"
                        variant="success"
                    >
                        Withdraw Profit
                    </x-ui.button>

                </div>
                    </form>

        </x-ui.card>
        @endif

    @if($showAddAccountForm)

        <x-ui.card
            title="Add Capital Account"
            description="Create a new capital account."
            class="mt-6"
        >

            <form
                wire:submit.prevent="addCapitalAccount"
                class="space-y-6"
            >

                <x-ui.input
                    label="Name"
                    name="name"
                    wire:model="name"
                />

                <x-ui.select
                    label="Branch"
                    name="branch"
                    wire:model="branch"
                >

                    <option value="">
                        Select Branch
                    </option>

                    @foreach(Branch::cases() as $case)

                        <option value="{{ $case->value }}">
                            {{ $case->name }}
                        </option>

                    @endforeach

                </x-ui.select>

                <x-ui.select
                    label="Currency"
                    name="currency"
                    wire:model="currency"
                >

                    <option value="">
                        Select Currency
                    </option>

                    @foreach(CurrencyType::cases() as $case)

                        <option value="{{ $case->value }}">
                            {{ $case->name }}
                        </option>

                    @endforeach

                </x-ui.select>

                <x-ui.select
                    label="Account Type"
                    name="account_type"
                    wire:model="account_type"
                >

                    <option value="">
                        Select Account Type
                    </option>

                    @foreach(CapitalAccountType::cases() as $case)

                        <option value="{{ $case->value }}">
                            {{ $case->name }}
                        </option>

                    @endforeach

                </x-ui.select>

                <x-ui.input
                    label="Opening Balance"
                    name="opening_balance"
                    type="number"
                    step="0.01"
                    wire:model="opening_balance"
                />
                <x-ui.textarea
                    label="Notes"
                    name="notes"
                    rows="3"
                    wire:model="notes"
                />
                <div class="flex justify-end gap-3">

                    <x-ui.button
                        type="button"
                        variant="secondary"
                        wire:click="closeAddAccountForm"
                    >
                        Cancel
                    </x-ui.button>

                    <x-ui.button
                        type="submit"
                    >
                        Add Capital Account
                    </x-ui.button>

                </div>
                </form>
        </x-ui.card>
    @endif

    @if($showEditAccountForm)

        <x-ui.card
            title="Edit Capital Account"
            description="Update account information."
            class="mt-6"
        >

            <form
                wire:submit.prevent="editCapitalAccount"
                class="space-y-6"
            >
                <x-ui.input
                    label="Name"
                    name="name"
                    wire:model="name"
                />

                <x-ui.textarea
                    label="Notes"
                    name="notes"
                    rows="3"
                    wire:model="notes"
                />
                <div class="flex justify-end gap-3">

                    <x-ui.button
                        type="button"
                        variant="secondary"
                        wire:click="closeEditAccountForm"
                    >
                        Cancel
                    </x-ui.button>

                    <x-ui.button
                        type="submit"
                    >
                        Update Capital Account
                    </x-ui.button>

                </div>
            </form>
        </x-ui.card>

    @endif
    @endcan


</div>
