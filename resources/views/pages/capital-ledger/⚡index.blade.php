<?php

use App\Enums\CapitalTransactionType;
use App\Models\CapitalAccount;
use App\Models\CapitalTransaction;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {

    use WithPagination;

    public ?int $selectedAccount = null;
    public ?string $selectedTransactionType = null;
    public ?string $transactionFromDate = null;
    public ?string $transactionToDate = null;

    #[Computed]
    public function accounts()
    {
        return CapitalAccount::query()
            ->latest()
            ->get();
    }

    #[Computed]
    public function transactions()
    {
        return CapitalTransaction::query()
            ->with([
                'account',
            ])
            ->when(
                $this->selectedAccount,
                fn ($query) => $query->where(
                    'capital_account_id',
                    $this->selectedAccount
                )
            )
            ->when(
                $this->selectedTransactionType,
                fn ($query) => $query->where(
                    'transaction_type',
                    $this->selectedTransactionType
                )
            )
            ->when(
                $this->transactionFromDate,
                fn ($query) => $query->whereDate(
                    'created_at',
                    '>=',
                    $this->transactionFromDate
                )
            )
            ->when(
                $this->transactionToDate,
                fn ($query) => $query->whereDate(
                    'created_at',
                    '<=',
                    $this->transactionToDate
                )
            )
            ->latest()
            ->paginate(10);
    }
};

?>

<div>

    <x-ui.page-header
        :title="__('capital_ledger.page.title')"
        :description="__('capital_ledger.page.description')"
    >
        <x-slot:actions>
            <x-ui.button
                :href="route('dashboard')"
                variant="secondary"
            >
                {{ app()->getLocale() === 'ar' ? '→' : '←' }}
                {{ __('capital_accounts.buttons.back') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>

        <x-ui.form-section
            :title="__('capital_ledger.sections.filters')"
            :description="__('capital_ledger.sections.filters_description')"
        >

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">

                <x-ui.select
                    wire:model.live="selectedAccount"
                    :label="__('capital_ledger.filters.account')"
                >
                    <option value="">
                        {{ __('capital_ledger.filters.all_accounts') }}
                    </option>

                    @foreach($this->accounts as $account)
                        <option value="{{ $account->id }}">
                            {{ $account->name }}
                            -
                            {{ $account->branch->label() }}
                        </option>
                    @endforeach

                </x-ui.select>

                <x-ui.select
                    wire:model.live="selectedTransactionType"
                    :label="__('capital_ledger.filters.transaction_type')"
                >
                    <option value="">
                        {{ __('capital_ledger.filters.all_transaction_types') }}
                    </option>

                    @foreach(\App\Enums\CapitalTransactionType::cases() as $type)
                        <option value="{{ $type->value }}">
                            {{ $type->label() }}
                        </option>
                    @endforeach

                </x-ui.select>

                <x-ui.input
                    type="date"
                    wire:model.live="transactionFromDate"
                    :label="__('capital_ledger.filters.from_date')"
                />

                <x-ui.input
                    type="date"
                    wire:model.live="transactionToDate"
                    :label="__('capital_ledger.filters.to_date')"
                />

            </div>

        </x-ui.form-section>

    </x-ui.card>


    <div class="mt-6">

        <x-ui.card
            :title="__('capital_ledger.sections.transactions')"
            :description="__('capital_ledger.sections.transactions_description')"
        >

            @if($this->transactions->isEmpty())

                <div class="py-8 text-center text-sm text-gray-500">
                    {{ __('capital_ledger.messages.empty') }}
                </div>

            @else

                <x-ui.table>

                    <x-ui.table-head>

                        <x-ui.table-row>

                            <x-ui.table-cell>
                                {{ __('capital_ledger.table.account') }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                {{ __('capital_ledger.table.amount') }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                {{ __('capital_ledger.table.direction') }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                {{ __('capital_ledger.table.balance_before') }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                {{ __('capital_ledger.table.balance_after') }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                {{ __('capital_ledger.table.transaction_type') }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                {{ __('capital_ledger.table.description') }}
                            </x-ui.table-cell>

                            <x-ui.table-cell>
                                {{ __('capital_ledger.table.date') }}
                            </x-ui.table-cell>

                        </x-ui.table-row>

                    </x-ui.table-head>

                    <x-ui.table-body>

                        @foreach($this->transactions as $transaction)

                            <x-ui.table-row>

                                <x-ui.table-cell>
                                    {{ $transaction->account->name }}
                                    -
                                    {{ $transaction->account->branch->label() }}
                                </x-ui.table-cell>

                                <x-ui.table-cell>

                                    <div class="flex items-center gap-2">

                                        <span class="{{ $transaction->direction->value === 'in'
                                                ? 'text-green-600'
                                                : 'text-red-600' }} font-bold">

                                            {{ $transaction->direction->value === 'in'
                                                ? '+'
                                                : '-' }}


                                            {{ number_format($transaction->amount,2) }}


                                            {{ $transaction->account->currency->label() }}

                                        </span>

                                    </div>

                                </x-ui.table-cell>

                                <x-ui.table-cell>

                                    {{ $transaction->direction->label() }}

                                </x-ui.table-cell>

                                <x-ui.table-cell>

                                    {{ number_format($transaction->balance_before,2) }}

                                    <span class="text-xs text-gray-500">

                                        {{ $transaction->account->currency->label() }}

                                    </span>

                                </x-ui.table-cell>

                                <x-ui.table-cell>

                                    {{ number_format($transaction->balance_after,2) }}

                                    <span class="text-xs text-gray-500">

                                        {{ $transaction->account->currency->label() }}

                                    </span>

                                </x-ui.table-cell>

                                <x-ui.table-cell>

                                    {{ $transaction->transaction_type->label() }}

                                </x-ui.table-cell>

                                <x-ui.table-cell>

                                    {{ $transaction->notes }}

                                </x-ui.table-cell>

                                <x-ui.table-cell>

                                    {{ $transaction->created_at->format('d M Y, h:i A') }}

                                </x-ui.table-cell>

                            </x-ui.table-row>

                        @endforeach

                    </x-ui.table-body>

                </x-ui.table>

                <div class="mt-6">

                    {{ $this->transactions->links() }}

                </div>

            @endif

        </x-ui.card>

    </div>

</div>
