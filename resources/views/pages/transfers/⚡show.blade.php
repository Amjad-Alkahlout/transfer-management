<?php

use App\Enums\CurrencyType;
use App\Enums\PaymentStatus;
use App\Enums\ReceiverMethod;
use App\Enums\TransferStatus;
use App\Models\BankAccount;
use App\Models\Transfer;
use App\Services\TransferExecutionService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Enums\TransferCalculationMode;


new class extends Component {

    use WithFileUploads;

    public Transfer $transfer;
    public $transfer_proof_path;
    public $show_transfer_proof_form = false;


    public function mount(Transfer $transfer)
    {
        $this->transfer = $transfer;
    }

    public function openTransferProofForm()
    {
        $this->show_transfer_proof_form = true;
    }

    public function hideTransferProofForm()
    {
        $this->show_transfer_proof_form = false;
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

            app(TransferExecutionService::class)
                ->execute($this->transfer, $path);

        } catch (\Throwable $e) {

            Storage::disk('public')->delete($path);

            throw $e;
        }

        $this->hideTransferProofForm();

        session()->flash(
            'complete_message',
            'Transfer completed successfully.'
        );
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
    <x-ui.page-header
        title="Transfer Details"
        :description="$transfer->reference_number"
    >

        <x-slot:actions>

            <div class="flex items-center gap-3">

                @switch($transfer->status)

                    @case(TransferStatus::PENDING)
                        <x-ui.badge color="yellow">
                            Pending
                        </x-ui.badge>
                        @break

                    @case(TransferStatus::COMPLETED)
                        <x-ui.badge color="green">
                            Completed
                        </x-ui.badge>
                        @break

                    @case(TransferStatus::CANCELLED)
                        <x-ui.badge color="red">
                            Cancelled
                        </x-ui.badge>
                        @break

                @endswitch

                @switch($transfer->payment_status)

                    @case(PaymentStatus::UNPAID)
                        <x-ui.badge color="red">
                            Unpaid
                        </x-ui.badge>
                        @break

                    @case(PaymentStatus::PARTIALLY_PAID)
                        <x-ui.badge color="orange">
                            Partially Paid
                        </x-ui.badge>
                        @break

                    @case(PaymentStatus::PAID)
                        <x-ui.badge color="green">
                            Paid
                        </x-ui.badge>
                        @break

                @endswitch

                <x-ui.button
                    :href="route('transfers.index')"
                    variant="secondary"
                >
                    ← Back
                </x-ui.button>

            </div>

        </x-slot:actions>

    </x-ui.page-header>

    {{-- Status --}}

    <x-ui.card title="Actions">

        <div class="flex flex-wrap gap-3">

            @if($transfer->payment_status !== PaymentStatus::PAID && $transfer->status !== TransferStatus::CANCELLED)

                <x-ui.button
                    :href="route('transfers.receive-payment',$transfer)"
                >
                    Receive Payment
                </x-ui.button>

            @endif

            @if($transfer->status === TransferStatus::PENDING)

                @if($transfer->payment_status === PaymentStatus::UNPAID)

                        <x-ui.button
                            :href="route('transfers.edit',$transfer)"
                            variant="secondary"
                        >
                            Edit
                        </x-ui.button>

                @endif

                    <x-ui.button
                        wire:click="openTransferProofForm"
                        variant="success"
                    >
                        Execute Transfer
                    </x-ui.button>

                    <x-ui.button
                        wire:click="cancelTransfer"
                        variant="danger"
                    >
                        Cancel Transfer
                    </x-ui.button>

            @endif

        </div>
    </x-ui.card>


    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    {{-- Receiver --}}
        <x-ui.card title="Receiver Information">

        <p>
            <strong>Reference:</strong>
            {{ $transfer->reference_number }}
        </p>

        <p>
            <strong>Name:</strong>
            {{ $transfer->receiver_name }}
        </p>

        <p>
            <strong>Method:</strong>
            {{ str($transfer->receiver_method->value)->replace('_', ' ')->title() }}
        </p>

        @if($transfer->receiver_method === ReceiverMethod::BANK)

            <p>
                <strong>Bank Account:</strong>
                {{ $transfer->receiver_account_number }}
            </p>

        @else

            <p>
                <strong>Wallet Number:</strong>
                {{ $transfer->receiver_wallet_phone }}
            </p>

        @endif
        </x-ui.card>


    {{-- Transfer --}}
        <x-ui.card title="Transfer Information">

        <p>
            <strong>Receiver Gets:</strong>
            {{ $transfer->transfer_amount }}
            {{ $transfer->requested_currency->symbol() }}
        </p>

        <p>
            <strong>Customer Pays:</strong>
            {{ $transfer->customer_payable_amount }}
            {{ $transfer->customer_payable_currency->symbol() }}
        </p>

        <p>
            <strong>Commission:</strong>
            {{ $transfer->commission_amount }}
            {{ $transfer->commission_currency->symbol() }}
        </p>
        @if($transfer->calculation_mode === TransferCalculationMode::RECEIVER_AMOUNT)
            <p>
                <strong>Fee Mode:</strong>
                {{ $transfer->fee_mode->name }}
            </p>
        @endif

        <p>
            <strong>Calculation Mode:</strong>

            {{ str($transfer->calculation_mode->value)->replace('_', ' ')->title() }}
        </p>

        </x-ui.card>
    </div>



    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    {{-- Payment --}}
        <x-ui.card title="Payment">

        <p>
            <strong>Paid:</strong>

            {{ $transfer->paid_amount }}
            {{ $transfer->customer_payable_currency->symbol() }}
        </p>

        <p>
            <strong>Remaining:</strong>

            {{ $transfer->remaining_amount }}
            {{ $transfer->customer_payable_currency->symbol() }}
        </p>

        </x-ui.card>




    {{-- Audit --}}
        <x-ui.card title="Audit">

        <p>
            <strong>Created By:</strong>

            {{ $transfer->creator?->name }}
        </p>

        <p>
            <strong>Created At:</strong>

            {{ $transfer->created_at->format('d/m/Y H:i') }}
        </p>

        @if($transfer->completed_at)

            <p>
                <strong>Completed At:</strong>

                {{ $transfer->completed_at->format('d/m/Y H:i') }}
            </p>

        @endif

        @if($transfer->cancelled_at)

            <p>
                <strong>Cancelled At:</strong>

                {{ $transfer->cancelled_at->format('d/m/Y H:i') }}
            </p>

         @endif

        </x-ui.card>
    </div>

    @if($transfer->transfer_proof_path)


        <x-ui.card title="Transfer Proof">

            <x-ui.button
                :href="Storage::url($transfer->transfer_proof_path)"
                target="_blank"
            >
                View Proof
            </x-ui.button>

        </x-ui.card>

    @endif


    @if($show_transfer_proof_form)

        <x-ui.card title="Execute Transfer">

            <form wire:submit.prevent="executeTransfer">

                <div>
                    <label>Transfer Proof</label>

                    <input
                        type="file"
                        wire:model="transfer_proof_path"
                    >

                    @error('transfer_proof_path')
                    <span>{{ $message }}</span>
                    @enderror
                </div>

                <br>

                <x-ui.button
                    type="submit"
                    variant="success"
                >
                    Execute Transfer
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="secondary"
                    wire:click="hideTransferProofForm"
                >
                    Cancel
                </x-ui.button>

            </form>


       </x-ui.card>

    @endif
</div>
