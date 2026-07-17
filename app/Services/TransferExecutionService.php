<?php

namespace App\Services;

use App\Enums\Branch;
use App\Enums\CapitalAccountType;
use App\Enums\CapitalTransactionType;
use App\Enums\CurrencyType;
use App\Enums\TransactionDirection;
use App\Enums\TransferStatus;
use App\Models\CapitalAccount;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;

class TransferExecutionService
{
    public function __construct(
        protected CurrencyConverterService $converter,
        protected CapitalTransferService $capitalTransferService,
        protected ProfitService $profitService,
    ) {
    }

    public function execute(
        Transfer $transfer,
        string $proofPath,
    ): void {

        DB::transaction(function () use ($transfer, $proofPath) {

            $gazaCapital = CapitalAccount::lockForUpdate()
                ->where('branch', Branch::GAZA)
                ->where('account_type', CapitalAccountType::CAPITAL)
                ->where('currency', CurrencyType::USD)
                ->firstOrFail();

            $profitAccount = CapitalAccount::lockForUpdate()
                ->where('branch', Branch::GAZA)
                ->where('account_type', CapitalAccountType::PROFIT)
                ->where('currency', CurrencyType::USD)
                ->firstOrFail();

            $commissionInUsd = $this->converter->convert(
                $transfer->commission_amount,
                $transfer->commission_currency,
                CurrencyType::USD,
            );

            $transferAmountInUsd = $this->converter->convert(
                $transfer->transfer_amount,
                $transfer->requested_currency,
                CurrencyType::USD,
            );

            $totalDeduction = $transferAmountInUsd + $commissionInUsd;

            if ($gazaCapital->balance < $totalDeduction) {
                throw new \RuntimeException(
                    'Insufficient balance in Gaza capital account.'
                );
            }

            $gazaBefore = $gazaCapital->balance;

            $gazaCapital->balance -= $transferAmountInUsd;

            $gazaCapital->save();

            $this->capitalTransferService->recordTransaction(
                account: $gazaCapital,
                direction: TransactionDirection::OUT,
                transactionType: CapitalTransactionType::CUSTOMER_TRANSFER,
                amount: $transferAmountInUsd,
                balanceBefore: $gazaBefore,
                balanceAfter: $gazaCapital->balance,
                createdBy: auth()->id(),
                reference: $transfer, // سنعدلها بعد قليل
            );

            $gazaBefore = $gazaCapital->balance;

            $gazaCapital->balance -= $commissionInUsd;

            $gazaCapital->save();

            $this->capitalTransferService->recordTransaction(
                account: $gazaCapital,
                direction: TransactionDirection::OUT,
                transactionType: CapitalTransactionType::TRANSFER_EXPENSE,
                amount: $commissionInUsd,
                balanceBefore: $gazaBefore,
                balanceAfter: $gazaCapital->balance,
                createdBy: auth()->id(),
                reference: $transfer,
            );

            $profitBefore = $profitAccount->balance;

            $profitAccount->balance += $commissionInUsd;

            $profitAccount->save();

            $this->capitalTransferService->recordTransaction(
                account: $profitAccount,
                direction: TransactionDirection::IN,
                transactionType: CapitalTransactionType::CUSTOMER_TRANSFER,
                amount: $commissionInUsd,
                balanceBefore: $profitBefore,
                balanceAfter: $profitAccount->balance,
                createdBy: auth()->id(),
                reference: $transfer,
            );

            $this->profitService->addCommission(
                $transfer,
                $commissionInUsd,
            );

            $transfer->update([
                'status' => TransferStatus::COMPLETED,
                'completed_at' => now(),
                'completed_by' => auth()->id(),
                'transfer_proof_path' => $proofPath,
            ]);


        });

    }
}
