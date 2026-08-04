<?php

namespace App\Services;

use App\Enums\CapitalTransactionType;
use App\Enums\PaymentStatus;
use App\Enums\TransactionDirection;
use App\Enums\TransferStatus;
use App\Models\CapitalAccount;
use App\Models\CapitalTransaction;
use App\Models\ProfitAccountTransaction;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;

class ForceCancelCompletedTransferService
{
    public function __construct(
        protected CapitalTransferService $capitalTransferService,
    ) {
    }

    public function cancel(Transfer $transfer, int $cancelledBy, ?string $notes = null): void
    {
        if ($transfer->status !== TransferStatus::COMPLETED) {
            throw new \RuntimeException(
                __('services.force_cancel.only_completed_allowed')
            );
        }

        DB::transaction(function () use ($transfer, $cancelledBy, $notes) {

            $originalTransactions = CapitalTransaction::query()
                ->where('reference_type', Transfer::class)
                ->where('reference_id', $transfer->id)
                ->orderBy('id')
                ->get();

            foreach ($originalTransactions as $original) {

                $account = CapitalAccount::lockForUpdate()
                    ->findOrFail($original->capital_account_id);

                $reversedDirection = $original->direction === TransactionDirection::IN
                    ? TransactionDirection::OUT
                    : TransactionDirection::IN;

                $before = $account->balance;

                $account->balance = $reversedDirection === TransactionDirection::IN
                    ? $account->balance + $original->amount
                    : $account->balance - $original->amount;

                $account->save();

                $this->capitalTransferService->recordTransaction(
                    account: $account,
                    direction: $reversedDirection,
                    transactionType: CapitalTransactionType::POST_COMPLETION_CANCELLATION,
                    amount: $original->amount,
                    balanceBefore: $before,
                    balanceAfter: $account->balance,
                    createdBy: $cancelledBy,
                    reference: $transfer,
                    notes: $notes,
                );
            }

            ProfitAccountTransaction::where('transfer_id', $transfer->id)->delete();

            $transfer->payments()->delete();

            $transfer->paid_amount = 0;
            $transfer->remaining_amount = 0;
            $transfer->payment_status = PaymentStatus::UNPAID;
            $transfer->status = TransferStatus::CANCELLED;
            $transfer->cancelled_by = $cancelledBy;
            $transfer->cancelled_at = now();

            $transfer->save();
        });
    }
}
