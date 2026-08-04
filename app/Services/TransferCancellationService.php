<?php

namespace App\Services;

use App\Enums\Branch;
use App\Enums\CapitalAccountType;
use App\Enums\CapitalTransactionType;
use App\Enums\PaymentStatus;
use App\Enums\TransactionDirection;
use App\Enums\TransferStatus;
use App\Models\CapitalAccount;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;

class TransferCancellationService
{
    public function __construct(
        protected CapitalTransferService $capitalTransferService,
    ) {
    }

    public function cancel(Transfer $transfer): void
    {
        if ($transfer->status !== TransferStatus::PENDING) {
            throw new \RuntimeException(
                __('services.transfer_cancellation.cannot_cancel')
            );
        }

        DB::transaction(function () use ($transfer) {

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
                    createdBy: auth()->id(),
                    reference: $transfer,
                    notes: null,
                );
            }

            $transfer->payments()->delete();
            $transfer->paid_amount = 0;

            $transfer->remaining_amount = 0;

            $transfer->payment_status = PaymentStatus::UNPAID;
            $transfer->status = TransferStatus::CANCELLED;
            $transfer->cancelled_by = auth()->id();
            $transfer->cancelled_at = now();

            $transfer->save();
        });
    }
}
