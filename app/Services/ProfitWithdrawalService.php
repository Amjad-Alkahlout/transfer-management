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
                    __('services.profit_withdrawal.invalid_account')
                );
            }

            if (! $profitAccount->is_active) {
                throw new \RuntimeException(
                    __('services.profit_withdrawal.inactive_account')
                );
            }

            if ($amount <= 0) {
                throw new \InvalidArgumentException(
                    __('services.profit_withdrawal.amount_must_be_positive')
                );
            }

            if ($profitAccount->balance < $amount) {
                throw new \RuntimeException(
                    __('services.profit_withdrawal.insufficient_balance')
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
