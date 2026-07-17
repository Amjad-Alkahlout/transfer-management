<?php

namespace App\Services;

use App\Enums\CapitalAccountType;
use App\Enums\CurrencyType;
use App\Models\CapitalAccount;
use App\Models\CapitalTransfer;
use App\Enums\CapitalTransactionType;
use App\Enums\TransactionDirection;
use App\Models\CapitalTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use InvalidArgumentException;
use App\Enums\Branch;

class CapitalTransferService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        protected CurrencyConverterService $converter,
    ) {
    }

    private function validateTransfer(
        CapitalAccount $fromAccount,
        CapitalAccount $toAccount,
        float $sourceAmount,
        float $transferCost,
    ): void {
        if ($fromAccount->is($toAccount)) {
            throw new RuntimeException(
                'Source and destination accounts cannot be the same.'
            );
        }
        if ($fromAccount->account_type !== CapitalAccountType::CAPITAL) {
            throw new RuntimeException('Source account must be a capital account.');
        }

        if ($toAccount->account_type !== CapitalAccountType::CAPITAL) {
            throw new RuntimeException('Destination account must be a capital account.');
        }

        if (! $fromAccount->is_active) {
            throw new RuntimeException('Source account is inactive.');
        }

        if (! $toAccount->is_active) {
            throw new RuntimeException('Destination account is inactive.');
        }

        // Validate that the fromAccount has enough balance
        if ($fromAccount->balance < $sourceAmount + $transferCost) {
            throw new RuntimeException('Insufficient balance in the source account.');
        }

        if ($sourceAmount <= 0) {
            throw new InvalidArgumentException(
                'Transfer amount must be greater than zero.'
            );
        }

        if ($transferCost < 0) {
            throw new InvalidArgumentException(
                'Transfer cost cannot be negative.'
            );
        }
    }

    private function getProfitAccount(CurrencyType $currency): CapitalAccount
    {
        $profitAccount= CapitalAccount::where('branch', Branch::GAZA)
            ->where('currency', $currency)
            ->where('account_type', CapitalAccountType::PROFIT)
            ->where('is_active', true)
            ->first();
        if (! $profitAccount) {
            throw new RuntimeException(
                'No profit account found for currency: ' . $currency->symbol()
            );
        }

        return $profitAccount;
    }

    public function preview(
        CapitalAccount $fromAccount,
        CapitalAccount $toAccount,
        float $sourceAmount,
        float $transferCost,
    ): array
    {
        $this->validateTransfer(
            $fromAccount,
            $toAccount,
            $sourceAmount,
            $transferCost,
        );

        $totalDeduction = $sourceAmount + $transferCost;

        $destinationAmount = $this->converter->convert(
            $sourceAmount,
            $fromAccount->currency,
            $toAccount->currency
        );

        if ($destinationAmount <= 0) {
            throw new \RuntimeException(
                'Calculated destination amount is invalid.'
            );
        }

        $exchangeRate = $this->converter->getExchangeRate(
            $fromAccount->currency,
            $toAccount->currency
        );

        return [
            'exchange_rate' => $exchangeRate,
            'destination_amount' => round($destinationAmount, 2),
            'total_deduction' => round($totalDeduction, 2),
        ];
    }

    public function recordTransaction(
        CapitalAccount $account,
        TransactionDirection $direction,
        CapitalTransactionType $transactionType,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        int $createdBy,
        ?Model $reference = null
    ): void {

        CapitalTransaction::create([
            'capital_account_id' => $account->id,

            'amount' => $amount,

            'direction' => $direction,

            'transaction_type' => $transactionType,

            'balance_before' => $balanceBefore,

            'balance_after' => $balanceAfter,

            'reference_type' => $reference?->getMorphClass(),

            'reference_id' => $reference?->getKey(),

            'created_by' => $createdBy,
        ]);
    }

    public function transfer(
        CapitalAccount $fromAccount,
        CapitalAccount $toAccount,
        float $sourceAmount,
        float $transferCost,
        int $createdBy,
        ?string $notes = null,
    ): CapitalTransfer {

        $this->validateTransfer(
            $fromAccount,
            $toAccount,
            $sourceAmount,
            $transferCost,
        );




        // Calculate the destination amount using the currency converter service
        $totalDeduction = $sourceAmount + $transferCost;

        $destinationAmount = round(
            $this->converter->convert($sourceAmount, $fromAccount->currency, $toAccount->currency),
            2
        );

        $exchangeRate = $this->converter->getExchangeRate(
            $fromAccount->currency,
            $toAccount->currency
        );

        if ($destinationAmount <= 0) {
            throw new \RuntimeException(
                'Calculated destination amount is invalid.'
            );
        }

        return DB::transaction(function () use (
            $fromAccount,
            $toAccount,
            $sourceAmount,
            $destinationAmount,
            $transferCost,
            $exchangeRate,
            $notes,
            $totalDeduction,
            $createdBy,
        ) {
            $fromAccount = CapitalAccount::lockForUpdate()->findOrFail($fromAccount->id);
            $toAccount = CapitalAccount::lockForUpdate()->findOrFail($toAccount->id);
            $profitAccount = CapitalAccount::lockForUpdate()->findOrFail(
                $this->getProfitAccount($toAccount->currency)->id
            );

            $profitDeduction = round(
                $this->converter->convert($transferCost, $fromAccount->currency, $profitAccount->currency),
                2
            );

            // Recheck after acquiring row locks to prevent race conditions.

            if ($fromAccount->balance < $totalDeduction) {
                throw new RuntimeException('Insufficient balance in the source account.');
            }
            $transfer = CapitalTransfer::create([
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,

                'source_amount' => $sourceAmount,
                'destination_amount' => $destinationAmount,

                'transfer_cost' => $transferCost,

                'exchange_rate' => $exchangeRate,

                'notes' => $notes,

                'created_by' => $createdBy
            ]);

            $fromBefore = $fromAccount->balance;

            $fromAfter = $fromBefore - $totalDeduction;

            $fromAccount->balance = $fromAfter;
            $fromAccount->save();

            $this->recordTransaction(
                account: $fromAccount,
                direction: TransactionDirection::OUT,
                transactionType: CapitalTransactionType::INTERNAL_TRANSFER,
                amount: $totalDeduction,
                balanceBefore: $fromBefore,
                balanceAfter: $fromAfter,
                createdBy: $createdBy,
                reference: $transfer,
            );

            $toBefore = $toAccount->balance;

            $toAfter = $toBefore + $destinationAmount;

            $toAccount->balance = $toAfter;
            $toAccount->save();
            $this->recordTransaction(
                account: $toAccount,
                direction: TransactionDirection::IN,
                transactionType: CapitalTransactionType::INTERNAL_TRANSFER,
                amount: $destinationAmount,
                balanceBefore: $toBefore,
                balanceAfter: $toAfter,
                createdBy: $createdBy,
                reference: $transfer,
            );

            $profitBefore = $profitAccount->balance;
            if ($profitBefore < $profitDeduction) {
                throw new RuntimeException(
                    'Insufficient balance in the Gaza profit account.'
                );
            }
            $profitAfter = $profitBefore - $profitDeduction;
            $profitAccount->balance = $profitAfter;
            $profitAccount->save();
            $this->recordTransaction(
                account: $profitAccount,
                direction: TransactionDirection::OUT,
                transactionType: CapitalTransactionType::TRANSFER_EXPENSE,
                amount: $profitDeduction,
                balanceBefore: $profitBefore,
                balanceAfter: $profitAfter,
                createdBy: $createdBy,
                reference: $transfer,
            );

            return $transfer;
        });

    }
}
