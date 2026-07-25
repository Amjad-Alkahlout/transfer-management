<?php

namespace App\Services;

use App\Enums\CapitalTransactionType;
use App\Enums\TransactionDirection;
use App\Models\CapitalAccount;
use Illuminate\Support\Facades\DB;

class CapitalAccountAdjustmentService
{
    public function __construct(
        protected CapitalTransferService $capitalTransferService,
    ) {
    }

    public function adjust(
        CapitalAccount $account,
        TransactionDirection $direction,
        float $amount,
        string $notes,
        int $createdBy,
    ): void {
        DB::transaction(function () use ($account, $direction, $amount, $notes, $createdBy) {

            $account = CapitalAccount::lockForUpdate()->findOrFail($account->id);

            if (! $account->is_active) {
                throw new \RuntimeException(
                    __('services.capital_account_adjustment.inactive_account')
                );
            }

            if ($amount <= 0) {
                throw new \InvalidArgumentException(
                    __('services.capital_account_adjustment.amount_must_be_positive')
                );
            }

            if ($direction === TransactionDirection::OUT && $account->balance < $amount) {
                throw new \RuntimeException(
                    __('services.capital_account_adjustment.insufficient_balance')
                );
            }

            $before = $account->balance;

            $after = $direction === TransactionDirection::OUT
                ? $before - $amount
                : $before + $amount;

            $account->balance = $after;
            $account->save();

            $this->capitalTransferService->recordTransaction(
                account: $account,
                direction: $direction,
                transactionType: CapitalTransactionType::MANUAL_ADJUSTMENT,
                amount: $amount,
                balanceBefore: $before,
                balanceAfter: $after,
                createdBy: $createdBy,
                reference: null,
            );
        });
    }
}
