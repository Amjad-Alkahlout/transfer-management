<?php

use App\Enums\CapitalAccountType;
use App\Models\CapitalAccount;
use App\Models\CapitalTransfer;
use App\Services\CapitalTransferService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $from_account_id;
    public $to_account_id;

    public $source_amount;
    public $transfer_cost = 0;

    public $notes;

    public ?array $preview = null;

    public bool $showTransferForm = false;

    public function mount()
    {
        Gate::authorize('view-capital-transfers');
    }

    public function updated($property)
    {

        if (in_array($property, [
            'source_amount',
            'from_account_id',
            'to_account_id',
            'transfer_cost',
        ])) {
            $this->resetErrorBag('preview');
            $this->calculatePreview();
        }
    }

    public function openTransferForm()
    {
        Gate::authorize('create-capital-transfer');
        $this->resetValidation();

        $this->reset([
            'from_account_id',
            'to_account_id',
            'source_amount',
            'notes',
            'preview',
        ]);
        $this->transfer_cost = 0;

        $this->showTransferForm = true;
    }

    public function closeTransferForm()
    {
        $this->resetValidation();
        $this->reset([
            'from_account_id',
            'to_account_id',
            'source_amount',
            'notes',
            'preview',
        ]);
        $this->transfer_cost = 0;

        $this->showTransferForm = false;
    }

    #[Computed]
    public function fromAccount(): ?CapitalAccount
    {
        return $this->from_account_id
            ? CapitalAccount::find($this->from_account_id)
            : null;
    }

    #[Computed]
    public function toAccount(): ?CapitalAccount
    {
        return $this->to_account_id
            ? CapitalAccount::find($this->to_account_id)
            : null;
    }

    #[Computed]
    public function accounts()
    {
        return CapitalAccount::query()
            ->where('is_active', true)
            ->where('account_type', CapitalAccountType::CAPITAL)
            ->orderBy('branch')
            ->orderBy('currency')
            ->get();
    }
    #[computed]
    public function transfers()
    {
        return CapitalTransfer::query()
            ->with([
                'fromAccount',
                'toAccount',
                'createdBy',
            ])
            ->latest()
            ->paginate(10);
    }

    public function calculatePreview()
    {
        if (!$this->from_account_id || !$this->to_account_id || !$this->source_amount) {
            $this->preview = null;
            return;
        }
        try{
            $from = CapitalAccount::findOrFail($this->from_account_id);

            $to = CapitalAccount::findOrFail($this->to_account_id);
            $this->preview = app(CapitalTransferService::class)
                ->preview(
                    $from,
                    $to,
                    (float) $this->source_amount,
                    (float) $this->transfer_cost,
                );
        } catch (\Exception $e) {
            $this->preview = null;

            $this->addError(
                'preview',
                $e->getMessage()
            );
        }
    }


    public function createTransfer()
    {
        Gate::authorize('create-capital-transfer');
        $this->validate([
            'from_account_id' => 'required|exists:capital_accounts,id',
            'to_account_id' => 'required|exists:capital_accounts,id|different:from_account_id',
            'source_amount' => 'required|numeric|min:0.01',
            'transfer_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $fromAccount = CapitalAccount::findOrFail($this->from_account_id);
        $toAccount = CapitalAccount::findOrFail($this->to_account_id);
        $this->resetErrorBag('preview');
        try {

            app(CapitalTransferService::class)->transfer(
                fromAccount: $fromAccount,
                toAccount: $toAccount,
                sourceAmount: $this->source_amount,
                transferCost: $this->transfer_cost,
                createdBy: auth()->id(),
                notes: $this->notes,
            );

        } catch (\Throwable $e) {
            $this->preview = null;
            $this->addError('preview', $e->getMessage());

            return;
        }

        $this->closeTransferForm();

        $this->resetPage();

        unset($this->transfers);

        session()->flash(
            'success',
            __('capital_transfers.messages.completed')
        );
    }
};
?>

<div>
    <x-ui.page-header
        :title="__('capital_transfers.page.title')"
        :description="__('capital_transfers.page.description')"
    >
        <x-slot:actions>
            @can('create-capital-transfer')
                <x-ui.button
                    wire:click="openTransferForm"
                >
                    {{ __('capital_transfers.buttons.new_transfer') }}
                </x-ui.button>
            @endcan

        </x-slot:actions>

    </x-ui.page-header>
    <x-ui.flash/>

    <div class="mb-6">

        <x-ui.button
            :href="route('dashboard')"
            variant="secondary"
        >
            @if(app()->getLocale() === 'ar')
                →
            @else
                ←
            @endif

            {{ __('capital_accounts.buttons.back') }}
        </x-ui.button>

    </div>

    <x-ui.card
        :title="__('capital_transfers.table.title')"
        :description="__('capital_transfers.table.description')"
    >

        <x-ui.table>

            <x-ui.table-header>
                <x-ui.table-row>

                    <x-ui.table-head>{{ __('capital_transfers.table.from_account') }}</x-ui.table-head>

                    <x-ui.table-head>{{ __('capital_transfers.table.to_account') }}</x-ui.table-head>

                    <x-ui.table-head>{{ __('capital_transfers.table.source_amount') }}</x-ui.table-head>

                    <x-ui.table-head>{{ __('capital_transfers.table.destination_amount') }}</x-ui.table-head>

                    <x-ui.table-head>{{ __('capital_transfers.table.transfer_cost') }}</x-ui.table-head>

                    <x-ui.table-head>{{ __('capital_transfers.table.exchange_rate') }}</x-ui.table-head>

                    <x-ui.table-head>{{ __('capital_transfers.table.created_by') }}</x-ui.table-head>

                    <x-ui.table-head>{{ __('capital_transfers.table.created_at') }}</x-ui.table-head>

                    <x-ui.table-head>{{ __('capital_transfers.table.notes') }}</x-ui.table-head>
                </x-ui.table-row>

            </x-ui.table-header>
            <x-ui.table-body>
                @foreach($this->transfers as $transfer)
                    <x-ui.table-row>
                        <x-ui.table-cell>{{ $transfer->fromAccount->name }}</x-ui.table-cell>
                        <x-ui.table-cell>{{ $transfer->toAccount->name }}</x-ui.table-cell>
                        <x-ui.table-cell>{{ $transfer->source_amount }} {{ $transfer->fromAccount->currency->symbol() }}</x-ui.table-cell>
                        <x-ui.table-cell>{{ $transfer->destination_amount }} {{ $transfer->toAccount->currency->symbol() }}</x-ui.table-cell>
                        <x-ui.table-cell>{{ $transfer->transfer_cost }}</x-ui.table-cell>
                        <x-ui.table-cell>{{ $transfer->exchange_rate }}</x-ui.table-cell>
                        <x-ui.table-cell>{{ $transfer->createdBy->name }}</x-ui.table-cell>
                        <x-ui.table-cell>{{ $transfer->created_at->format('Y-m-d H:i') }}</x-ui.table-cell>
                        <x-ui.table-cell>{{ $transfer->notes }}</x-ui.table-cell>
                    </x-ui.table-row>
                @endforeach
            </x-ui.table-body>
        </x-ui.table>

        <x-slot:footer>

            {{ $this->transfers->links() }}

        </x-slot:footer>

    </x-ui.card>

    @if($showTransferForm)

        <x-ui.card
            :title="__('capital_transfers.form.title')"
            :description="__('capital_transfers.form.description')"
        >

            <form
                wire:submit.prevent="createTransfer"
                class="space-y-6"
            >
                <x-ui.select
                    :label="__('capital_transfers.fields.from_account')"
                    name="from_account_id"
                    wire:model.live="from_account_id"
                >

                    <option value="">
                        {{ __('capital_transfers.placeholders.select_account') }}
                    </option>

                    @foreach($this->accounts as $account)

                        <option value="{{ $account->id }}">
                            {{ $account->name }}
                            -
                            {{ $account->branch->label() }}
                            -
                            {{ $account->currency->label() }}
                        </option>

                    @endforeach

                </x-ui.select>

                <x-ui.select
                    :label="__('capital_transfers.fields.to_account')"
                    name="to_account_id"
                    wire:model.live="to_account_id"
                >
                    <option value="">{{ __('capital_transfers.placeholders.select_account') }}</option>
                    @foreach($this->accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}
                            -
                            {{ $account->branch->label() }}
                            -
                            {{ $account->currency->label() }}</option>
                    @endforeach
                </x-ui.select>



                <x-ui.input
                    :label="__('capital_transfers.fields.amount')"
                    name="source_amount"
                    type="number"
                    step="0.01"
                    wire:model.live.number="source_amount"
                />

                <x-ui.input
                    :label="__('capital_transfers.fields.transfer_cost')"
                    name="transfer_cost"
                    type="number"
                    step="0.01"
                    wire:model.live.number="transfer_cost"
                />

                <x-ui.textarea
                    :label="__('capital_transfers.fields.notes')"
                    name="notes"
                    rows="3"
                    wire:model="notes"
                />

                @error('preview')

                <x-ui.alert color="danger">
                    {{ $message }}
                </x-ui.alert>

                @enderror

                @if($preview)

                    <x-ui.card
                        :title="__('capital_transfers.preview.title')"
                        :description="__('capital_transfers.preview.description')"
                    >

                        <div class="grid grid-cols-2 gap-y-4 items-center">

                            <div class="text-sm text-gray-500 text-start">
                                {{ __('capital_transfers.preview.from_account') }}
                            </div>

                            <div class="font-semibold text-start">
                                {{ $this->fromAccount?->name }}
                            </div>

                            <div class="text-sm text-gray-500 text-start">
                                {{ __('capital_transfers.preview.to_account') }}
                            </div>

                            <div class="font-semibold text-start">
                                {{ $this->toAccount?->name }}
                            </div>

                            <div class="text-sm text-gray-500 text-start">
                                {{ __('capital_transfers.preview.transfer_amount') }}
                            </div>

                            <div class="font-semibold text-start">
                                {{ number_format($source_amount,2) }}
                                {{ $this->fromAccount?->currency->symbol() }}
                            </div>

                            <div class="text-sm text-gray-500 text-start">
                                {{ __('capital_transfers.preview.transfer_cost') }}
                            </div>

                            <div class="font-semibold text-start">
                                {{ number_format($transfer_cost,2) }}
                                {{ $this->fromAccount?->currency->symbol() }}
                            </div>

                            <div class="text-sm text-gray-500 text-start">
                                {{ __('capital_transfers.preview.total_deduction') }}
                            </div>

                            <div class="rounded-lg bg-red-50 px-3 py-2 font-semibold text-red-700">
                                {{ number_format($preview['total_deduction'],2) }}
                                {{ $this->fromAccount?->currency->symbol() }}
                            </div>

                            <div class="text-sm text-gray-500 text-start">
                                {{ __('capital_transfers.preview.exchange_rate') }}
                            </div>

                            <div class="font-semibold text-start">
                                1 {{ $this->fromAccount?->currency->symbol() }}
                                =
                                {{ number_format($preview['exchange_rate'], 4) }}
                                {{ $this->toAccount?->currency->symbol() }}
                            </div>

                            <div class="text-sm text-gray-500">
                                {{ __('capital_transfers.preview.destination_amount') }}
                            </div>

                            <div class="rounded-lg bg-green-50 px-3 py-2 font-semibold text-green-700">
                                {{ number_format($preview['destination_amount'],2) }}
                                {{ $this->toAccount?->currency->symbol() }}
                            </div>

                        </div>

                    </x-ui.card>

                @endif

                <div class="flex justify-end gap-3">

                    <x-ui.button
                        type="button"
                        variant="secondary"
                        wire:click="closeTransferForm"
                    >
                        {{ __('capital_transfers.buttons.cancel') }}
                    </x-ui.button>

                    <x-ui.button
                        type="submit"
                    >
                        {{ __('capital_transfers.buttons.submit') }}
                    </x-ui.button>

                </div>
            </form>
        </x-ui.card>
    @endif

</div>
