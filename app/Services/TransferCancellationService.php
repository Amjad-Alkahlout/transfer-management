<?php

namespace App\Services;

use App\Enums\Branch;
use App\Enums\CapitalAccountType;
use App\Enums\CapitalTransactionType;
use App\Enums\PaymentStatus;
use App\Enums\TransactionDirection;
use App\Enums\TransferStatus;
use App\Events\TransferCancelled;
use App\Models\CapitalAccount;
use App\Models\Transfer;
use DomainException;
use Illuminate\Support\Facades\DB;

class TransferCancellationService
{
    public function __construct(
        protected CapitalTransferService $capitalTransferService,
    ) {}

    public function cancel(Transfer $transfer, int $cancelledBy): Transfer
    {
        $transfer = DB::transaction(function () use ($transfer, $cancelledBy): Transfer {
            $transfer = Transfer::query()->lockForUpdate()->findOrFail($transfer->id);

            if ($transfer->status !== TransferStatus::PENDING) {
                throw new DomainException(__('services.transfer_cancellation.cannot_cancel'));
            }

            if ($transfer->paid_amount > 0) {

                $capitalAccount = CapitalAccount::lockForUpdate()
                    ->where('branch', Branch::UAE)
                    ->where('account_type', CapitalAccountType::CAPITAL)
                    ->where('currency', $transfer->customer_payable_currency)
                    ->firstOrFail();

                $before = $capitalAccount->balance;

                $capitalAccount->balance -= $transfer->paid_amount;

                $capitalAccount->save();

                $this->capitalTransferService->recordTransaction(
                    account: $capitalAccount,
                    direction: TransactionDirection::OUT,
                    transactionType: CapitalTransactionType::TRANSFER_CANCELLATION,
                    amount: $transfer->paid_amount,
                    balanceBefore: $before,
                    balanceAfter: $capitalAccount->balance,
                    createdBy: $cancelledBy,
                    reference: $transfer,
                    notes: null,
                );
            }

            $transfer->payments()->delete();
            $transfer->paid_amount = 0;

            $transfer->remaining_amount = 0;

            $transfer->payment_status = PaymentStatus::UNPAID;
            $transfer->status = TransferStatus::CANCELLED;
            $transfer->cancelled_by = $cancelledBy;
            $transfer->cancelled_at = now();

            $transfer->save();

            return $transfer->fresh(['creator']);
        });

        TransferCancelled::dispatch($transfer);

        return $transfer;
    }
}
