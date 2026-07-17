<?php

namespace App\Services;

use App\Enums\CapitalAccountType;
use App\Enums\CapitalTransactionType;
use App\Enums\TransactionDirection;
use App\Models\CapitalAccount;
use Illuminate\Support\Facades\DB;

class ProfitWithdrawalService
{
    public function __construct(
        protected CapitalTransferService $capitalTransferService,
    ) {
    }

    public function withdraw(
        CapitalAccount $profitAccount,
        float $amount,
        ?string $notes = null,
    ): void
    {
        DB::transaction(function () use ($profitAccount, $amount, $notes) {

            $profitAccount = CapitalAccount::lockForUpdate()
                ->findOrFail($profitAccount->id);

            if ($profitAccount->account_type !== CapitalAccountType::PROFIT) {
                throw new \RuntimeException(
                    'Selected account is not a profit account.'
                );
            }

            if (! $profitAccount->is_active) {
                throw new \RuntimeException(
                    'Profit account is inactive.'
                );
            }

            if ($amount <= 0) {
                throw new \InvalidArgumentException(
                    'Amount must be greater than zero.'
                );
            }

            if ($profitAccount->balance < $amount) {
                throw new \RuntimeException(
                    'Insufficient balance.'
                );
            }

            $before = $profitAccount->balance;

            $after = $before - $amount;

            $profitAccount->balance = $after;

            $profitAccount->save();

            $this->capitalTransferService->recordTransaction(
                account: $profitAccount,
                direction: TransactionDirection::OUT,
                transactionType: CapitalTransactionType::PROFIT_WITHDRAWAL,
                amount: $amount,
                balanceBefore: $before,
                balanceAfter: $after,
                createdBy: auth()->id(),
                reference: null,
            );

        });
    }
}
