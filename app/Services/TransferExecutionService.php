<?php

namespace App\Services;

use App\Enums\Branch;
use App\Enums\CapitalAccountType;
use App\Enums\CapitalTransactionType;
use App\Enums\CurrencyType;
use App\Enums\TransactionDirection;
use App\Enums\TransferStatus;
use App\Events\TransferExecuted;
use App\Models\CapitalAccount;
use App\Models\Transfer;
use DomainException;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransferExecutionService
{
    public function __construct(
        protected CurrencyConverterService $converter,
        protected CapitalTransferService $capitalTransferService,
        protected ProfitService $profitService,
    ) {}

    public function execute(
        Transfer $transfer,
        string $proofPath,
        int $executedBy,
    ): Transfer {

        $transfer = DB::transaction(function () use ($transfer, $proofPath, $executedBy): Transfer {
            $transfer = Transfer::query()->lockForUpdate()->findOrFail($transfer->id);

            if ($transfer->status !== TransferStatus::PENDING) {
                throw new DomainException(__('transfers.errors.cannot_execute'));
            }

            $gazaCapital = CapitalAccount::lockForUpdate()
                ->where('branch', Branch::GAZA)
                ->where('account_type', CapitalAccountType::CAPITAL)
                ->where('currency', CurrencyType::USD)
                ->where('is_active', true)
                ->firstOrFail();

            $profitAccount = CapitalAccount::lockForUpdate()
                ->where('branch', Branch::GAZA)
                ->where('account_type', CapitalAccountType::PROFIT)
                ->where('currency', CurrencyType::USD)
                ->where('is_active', true)
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
                    __('services.transfer_execution.insufficient_gaza_capital')
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
                createdBy: $executedBy,
                reference: $transfer,
                notes: null,
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
                createdBy: $executedBy,
                reference: $transfer,
                notes: null,
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
                createdBy: $executedBy,
                reference: $transfer,
                notes: null,
            );

            $this->profitService->addCommission(
                $transfer,
                $commissionInUsd,
                $executedBy,
            );

            $transfer->update([
                'status' => TransferStatus::COMPLETED,
                'completed_at' => now(),
                'completed_by' => $executedBy,
                'transfer_proof_path' => $proofPath,
            ]);

            return $transfer->fresh(['creator']);
        });

        try {
            TransferExecuted::dispatch($transfer);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $transfer;
    }
}
