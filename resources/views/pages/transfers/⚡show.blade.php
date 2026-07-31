<?php

use App\Enums\CurrencyType;
use App\Enums\PaymentStatus;
use App\Enums\ReceiverMethod;
use App\Enums\TransferStatus;
use App\Events\TransferCancelled;
use App\Events\TransferExecuted;
use App\Models\CapitalTransaction;
use App\Models\Transfer;
use App\Services\TransferExecutionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Enums\TransferCalculationMode;
use App\Services\TransferCancellationService;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Gate;

new #[Layout('layouts::app')]
class extends Component {

    use WithFileUploads;

    public Transfer $transfer;
    public $transfer_proof_path;
    public $show_transfer_proof_form = false;
    public bool $showProofModal = false;



    public function mount(Transfer $transfer)
    {
        Gate::authorize('view-transfers');
        $this->transfer = $transfer;
    }

    public function openProofModal(): void
    {
        $this->showProofModal = true;
    }

    public function closeProofModal(): void
    {
        $this->showProofModal = false;
    }


    public function openTransferProofForm()
    {
        Gate::authorize('execute-transfer');
        $this->show_transfer_proof_form = true;
    }

    public function hideTransferProofForm()
    {
        $this->show_transfer_proof_form = false;
    }


    public function executeTransfer()
    {
        Gate::authorize('execute-transfer');
        if ($this->transfer->status !== TransferStatus::PENDING) {
            $this->addError(
                'general',
                __('transfers.errors.cannot_execute')
            );
            return;
        }

        $this->validate([
            'transfer_proof_path' => 'required|file|mimes:jpg,jpeg,png,pdf|mimetypes:image/jpeg,image/png,application/pdf|max:2048',
        ]);

        $path = $this->transfer_proof_path->store('transfer_proofs', 'public');

        try {

            app(TransferExecutionService::class)
                ->execute($this->transfer, $path);

        } catch (\Throwable $e) {

            Storage::disk('public')->delete($path);

            $this->addError(
                'general',
                $e->getMessage()
            );

            return;
        }

        $this->hideTransferProofForm();
        event(new TransferExecuted($this->transfer));

        session()->flash(
            'success',
            __('transfers.messages.completed')
        );
        return redirect()->route(
            'transfers.show',
            $this->transfer
        );
    }

    public function cancelTransfer()
    {
        Gate::authorize('cancel-transfer');
        if ($this->transfer->status !== TransferStatus::PENDING) {
            $this->addError(
                'general',
                __('transfers.errors.cannot_cancel')
            );
            return;
        }
        try {

            app(TransferCancellationService::class)
                ->cancel($this->transfer);

        } catch (\Throwable $e) {

            $this->addError(
                'general',
                $e->getMessage()
            );

            return;
        }
        event(new TransferCancelled($this->transfer));
        session()->flash(
            'success',
            __('transfers.messages.cancelled')
        );

        return redirect()->route(
            'transfers.show',
            $this->transfer
        );

    }

    #[Computed]

    public function transactions()
    {
        return CapitalTransaction::query()
            ->with([
                'account',
            ])
            ->where('reference_type', Transfer::class)
            ->where('reference_id', $this->transfer->id)
            ->latest()
            ->get();
    }

};
?>

<div>
    <x-ui.page-header
        :title="__('transfers.page.show_title')"
        :description="$transfer->reference_number"
    >

        <x-slot:actions>

            <div class="flex items-center gap-3">

                @switch($transfer->status)

                    @case(TransferStatus::PENDING)
                        <x-ui.badge color="yellow">
                            {{ TransferStatus::PENDING->label() }}
                        </x-ui.badge>
                        @break

                    @case(TransferStatus::COMPLETED)
                        <x-ui.badge color="green">
                            {{ TransferStatus::COMPLETED->label() }}
                        </x-ui.badge>
                        @break

                    @case(TransferStatus::CANCELLED)
                        <x-ui.badge color="red">
                            {{ TransferStatus::CANCELLED->label() }}
                        </x-ui.badge>
                        @break

                @endswitch

                @switch($transfer->payment_status)

                    @case(PaymentStatus::UNPAID)
                        <x-ui.badge color="red">
                            {{ PaymentStatus::UNPAID->label() }}
                        </x-ui.badge>
                        @break

                    @case(PaymentStatus::PARTIALLY_PAID)
                        <x-ui.badge color="orange">
                            {{ PaymentStatus::PARTIALLY_PAID->label() }}
                        </x-ui.badge>
                        @break

                    @case(PaymentStatus::PAID)
                        <x-ui.badge color="green">
                            {{ PaymentStatus::PAID->label() }}
                        </x-ui.badge>
                        @break

                @endswitch

                <x-ui.button
                    :href="route('transfers.index')"
                    variant="secondary"
                >
                    {{ app()->getLocale() === 'ar' ? '→' : '←' }}
                    {{ __('transfers.buttons.back') }}
                </x-ui.button>

            </div>

        </x-slot:actions>

    </x-ui.page-header>


    {{-- Status --}}

    @if($transfer->status !== TransferStatus::CANCELLED)

        <x-ui.card :title="__('transfers.sections.actions')">

            <div class="flex flex-wrap gap-3">
                @can('receive-payment')
                    @if($transfer->payment_status !== PaymentStatus::PAID && $transfer->status !== TransferStatus::CANCELLED)

                        <x-ui.button
                            :href="route('transfers.receive-payment',$transfer)"
                        >
                            {{ __('transfers.buttons.receive_payment') }}
                        </x-ui.button>

                    @endif
                @endcan

                @if($transfer->transfer_proof_path)
                    <x-ui.button
                        wire:click="openProofModal"
                    >
                        {{ __('transfers.buttons.view_proof') }}
                    </x-ui.button>
                @endif

                @if($transfer->status === TransferStatus::PENDING)
                    @can('update-transfer')
                        @if($transfer->payment_status === PaymentStatus::UNPAID)

                            <x-ui.button
                                :href="route('transfers.edit',$transfer)"
                                variant="secondary"
                            >
                                {{ __('transfers.buttons.edit') }}
                            </x-ui.button>

                        @endif
                    @endcan
                    @can('execute-transfer')
                        <x-ui.button
                            wire:click="openTransferProofForm"
                            variant="success"
                        >
                            {{ __('transfers.buttons.execute') }}
                        </x-ui.button>
                    @endcan
                    @can('cancel-transfer')
                        <x-ui.button
                            wire:click="cancelTransfer"
                            variant="danger"
                        >
                            {{ __('transfers.buttons.cancel_transfer') }}
                        </x-ui.button>
                    @endcan

                @endif

            </div>
        </x-ui.card>

    @endif


    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Receiver --}}
        <x-ui.card :title="__('transfers.sections.receiver')">

            <p>
                <strong>{{ __('transfers.details.reference') }}:</strong>
                {{ $transfer->reference_number }}
            </p>

            <p>
                <strong>{{ __('transfers.details.name') }}:</strong>
                {{ $transfer->receiver_name }}
            </p>

            <p>
                <strong>{{ __('transfers.details.method') }}:</strong>
                {{ $transfer->receiver_method->label() }}
            </p>

            @if($transfer->receiver_method === ReceiverMethod::BANK)

                <p>
                    <strong>{{ __('transfers.details.bank_account') }}:</strong>
                    {{ $transfer->receiver_account_number }}
                </p>

            @else

                <p>
                    <strong>{{ __('transfers.details.wallet_number') }}:</strong>
                    {{ $transfer->receiver_wallet_phone }}
                </p>

            @endif
        </x-ui.card>


        {{-- Transfer --}}
        <x-ui.card :title="__('transfers.sections.transfer')">

            <p>
                <strong>{{ __('transfers.details.receiver_gets') }}:</strong>
                {{ $transfer->transfer_amount }}
                {{ $transfer->requested_currency->symbol() }}
            </p>

            <p>
                <strong>{{ __('transfers.details.customer_pays') }}:</strong>
                {{ $transfer->customer_payable_amount }}
                {{ $transfer->customer_payable_currency->symbol() }}
            </p>

            <p>
                <strong>{{ __('transfers.details.commission') }}:</strong>
                {{ $transfer->commission_amount }}
                {{ $transfer->commission_currency->symbol() }}
            </p>


            <p>
                <strong>{{ __('transfers.details.calculation_mode') }}:</strong>

                {{ $transfer->calculation_mode->label() }}
            </p>

        </x-ui.card>
    </div>


    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2 mb-4">
        {{-- Payment --}}
        <x-ui.card :title="__('transfers.sections.payment')">

            <p>
                <strong>{{ __('transfers.details.paid') }}:</strong>

                {{ $transfer->paid_amount }}
                {{ $transfer->customer_payable_currency->symbol() }}
            </p>

            <p>
                <strong>{{ __('transfers.details.remaining') }}:</strong>

                {{ $transfer->remaining_amount }}
                {{ $transfer->customer_payable_currency->symbol() }}
            </p>

        </x-ui.card>


        {{-- Audit --}}
        <x-ui.card :title="__('transfers.sections.audit')">

            @if($transfer->notes)

                <p>
                    <strong>{{ __('transfers.details.notes') }}:</strong>
                    {{ $transfer->notes }}
                </p>

            @endif

            <p>
                <strong>{{ __('transfers.details.created_by') }}:</strong>

                {{ $transfer->creator?->name }}
            </p>

            <p>
                <strong>{{ __('transfers.details.created_at') }}:</strong>

                {{ $transfer->created_at->format('d/m/Y H:i') }}
            </p>

            @if($transfer->completed_at)

                <p>
                    <strong>{{ __('transfers.details.completed_at') }}:</strong>

                    {{ $transfer->completed_at->format('d/m/Y H:i') }}
                </p>

            @endif

            @if($transfer->cancelled_at)

                <p>
                    <strong>{{ __('transfers.details.cancelled_at') }}:</strong>

                    {{ $transfer->cancelled_at->format('d/m/Y H:i') }}
                </p>

            @endif

        </x-ui.card>

    </div>
    @can('view-capital-ledger')
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-1 mb-4">
        <x-ui.card :title="__('transfers.sections.financial_impact')">

            @if($this->transactions->isEmpty())

                <div class="py-8 text-center text-sm text-gray-500">
                    {{ __('transfers.messages.no_capital_transactions') }}
                </div>

            @else

                <x-ui.table>

                    <x-ui.table-head>

                        <x-ui.table-row>
                            <x-ui.table-cell>{{ __('transfers.ledger.account') }}</x-ui.table-cell>
                            <x-ui.table-cell>{{ __('transfers.ledger.amount') }}</x-ui.table-cell>
                            <x-ui.table-cell>{{ __('transfers.ledger.direction') }}</x-ui.table-cell>
                            <x-ui.table-cell>{{ __('transfers.ledger.balance_before') }}</x-ui.table-cell>
                            <x-ui.table-cell>{{ __('transfers.ledger.balance_after') }}</x-ui.table-cell>
                            <x-ui.table-cell>{{ __('transfers.ledger.description') }}</x-ui.table-cell>
                            <x-ui.table-cell>{{ __('transfers.ledger.date') }}</x-ui.table-cell>
                        </x-ui.table-row>

                    </x-ui.table-head>

                    <x-ui.table-body>

                        @foreach($this->transactions as $transaction)

                            @php
                                $currency = $transaction->account->currency;
                            @endphp

                            <x-ui.table-row>

                                <x-ui.table-cell>
                                    {{ $transaction->account->name }}
                                    -
                                    {{ $transaction->account->branch->label() }}
                                </x-ui.table-cell>

                                <x-ui.table-cell>

                            <span class="{{ $transaction->direction->value === 'in'
                                    ? 'text-green-600'
                                    : 'text-red-600' }} font-semibold">

                                {{ $transaction->direction->value === 'in' ? '+' : '-' }}

                                {{ number_format($transaction->amount, 2) }}

                                {{ $currency->label() }}

                            </span>

                                </x-ui.table-cell>

                                <x-ui.table-cell>
                                    {{ $transaction->direction->label() }}
                                </x-ui.table-cell>

                                <x-ui.table-cell>
                                    {{ number_format($transaction->balance_before, 2) }}
                                    {{ $currency->label() }}
                                </x-ui.table-cell>

                                <x-ui.table-cell>
                                    {{ number_format($transaction->balance_after, 2) }}
                                    {{ $currency->label() }}
                                </x-ui.table-cell>

                                <x-ui.table-cell>
                                    {{ $transaction->description }}
                                </x-ui.table-cell>

                                <x-ui.table-cell>
                                    {{ $transaction->created_at->format('d/m/Y H:i') }}
                                </x-ui.table-cell>

                            </x-ui.table-row>

                        @endforeach

                    </x-ui.table-body>

                </x-ui.table>

            @endif
        </x-ui.card>
    </div>
    @endcan


    @if($show_transfer_proof_form)

        <x-ui.card :title="__('transfers.sections.execute_transfer')">

            @error('general')
            <x-ui.alert color="danger">
                {{ $message }}
            </x-ui.alert>
            @enderror

            <form wire:submit.prevent="executeTransfer">

                <x-ui.input
                    type="file"
                    :label="__('transfers.fields.transfer_proof')"
                    name="transfer_proof_path"
                    wire:model="transfer_proof_path"
                    class="mb-2"
                />

                <x-ui.button
                    type="submit"
                    variant="success"
                >
                    {{ __('transfers.buttons.execute') }}
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="secondary"
                    wire:click="hideTransferProofForm"
                >
                    {{ __('transfers.buttons.cancel') }}
                </x-ui.button>

            </form>


        </x-ui.card>

    @endif

    <x-ui.modal
        :show="$showProofModal"
        close="$set('showProofModal', false)"
        :title="__('transfers.modal.proof_title')"
        maxWidth="6xl"
    >

        <img
            src="{{ Storage::url($transfer->transfer_proof_path) }}"
            class="mx-auto max-h-[80vh] max-w-full rounded-lg object-contain"
        >

        <x-slot:footer>

            <div class="flex justify-end gap-2">

                @php
                    $extension = pathinfo($transfer->transfer_proof_path, PATHINFO_EXTENSION);
                @endphp

                <x-ui.button
                    :href="Storage::url($transfer->transfer_proof_path)"
                    :download="'Transfer-Proof: '.$transfer->receiver_name.'.'.$extension"
                    variant="secondary"
                >
                    {{ __('transfers.buttons.download') }}
                </x-ui.button>

                <x-ui.button
                    variant="secondary"
                    wire:click="$set('showProofModal', false)"
                >
                    {{ __('transfers.buttons.close') }}
                </x-ui.button>

            </div>

        </x-slot:footer>

    </x-ui.modal>
</div>
