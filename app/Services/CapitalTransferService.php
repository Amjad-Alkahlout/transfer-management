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

    /**
     * Special case: profit distribution from Gaza's profit account
     * directly to the UAE capital account, with no transfer cost.
     */
    private function isProfitDistribution(CapitalAccount $fromAccount, CapitalAccount $toAccount): bool
    {
        return $fromAccount->account_type === CapitalAccountType::PROFIT
            && $fromAccount->branch === Branch::GAZA
            && $toAccount->account_type === CapitalAccountType::CAPITAL
            && $toAccount->branch === Branch::UAE;
    }

    private function validateTransfer(
        CapitalAccount $fromAccount,
        CapitalAccount $toAccount,
        float $sourceAmount,
        float $transferCost,
    ): void {
        if ($fromAccount->is($toAccount)) {
            throw new RuntimeException(
                __('services.capital_transfer.same_account')
            );
        }

        $isProfitDistribution = $this->isProfitDistribution($fromAccount, $toAccount);

        if (! $isProfitDistribution && $fromAccount->account_type !== CapitalAccountType::CAPITAL) {
            throw new RuntimeException(__('services.capital_transfer.source_must_be_capital'));
        }

        if ($toAccount->account_type !== CapitalAccountType::CAPITAL) {
            throw new RuntimeException(__('services.capital_transfer.destination_must_be_capital'));
        }

        if (! $fromAccount->is_active) {
            throw new RuntimeException(__('services.capital_transfer.source_inactive'));
        }

        if (! $toAccount->is_active) {
            throw new RuntimeException(__('services.capital_transfer.destination_inactive'));
        }

        // Validate that the fromAccount has enough balance
        if ($fromAccount->balance < $sourceAmount ) {
            throw new RuntimeException(__('services.capital_transfer.insufficient_source_balance'));
        }

        if ($sourceAmount <= 0) {
            throw new InvalidArgumentException(
                __('services.capital_transfer.amount_must_be_positive')
            );
        }

        if ($transferCost < 0) {
            throw new InvalidArgumentException(
                __('services.capital_transfer.cost_cannot_be_negative')
            );
        }

        if ($isProfitDistribution && $transferCost > 0) {
            throw new InvalidArgumentException(
                __('services.capital_transfer.profit_distribution_no_cost')
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
                __(
                    'services.capital_transfer.profit_account_not_found',
                    [
                        'currency' => $currency->symbol(),
                    ]
                )
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
                __('services.capital_transfer.invalid_destination_amount')
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
        ?Model $reference = null,
        ?string $notes = null,
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
            'notes' => $notes,
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

        $isProfitDistribution = $this->isProfitDistribution($fromAccount, $toAccount);


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
                __('services.capital_transfer.invalid_destination_amount')
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
            $createdBy,
            $isProfitDistribution,
        ) {
            $fromAccount = CapitalAccount::lockForUpdate()->findOrFail($fromAccount->id);
            $toAccount = CapitalAccount::lockForUpdate()->findOrFail($toAccount->id);

            $profitAccount = null;
            $profitDeduction = 0;

            if (! $isProfitDistribution) {
                $profitAccount = CapitalAccount::lockForUpdate()->findOrFail(
                    $this->getProfitAccount($toAccount->currency)->id
                );

                $profitDeduction = round(
                    $this->converter->convert($transferCost, $fromAccount->currency, $profitAccount->currency),
                    2
                );
            }

            // Recheck after acquiring row locks to prevent race conditions.
            if ($fromAccount->balance < $sourceAmount) {
                throw new RuntimeException(__('services.capital_transfer.insufficient_source_balance'));
            }

            $transactionType = $isProfitDistribution
                ? CapitalTransactionType::PROFIT_DISTRIBUTION
                : CapitalTransactionType::INTERNAL_TRANSFER;

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

            $fromAfter = $fromBefore - $sourceAmount;

            $fromAccount->balance = $fromAfter;
            $fromAccount->save();

            $this->recordTransaction(
                account: $fromAccount,
                direction: TransactionDirection::OUT,
                transactionType: $transactionType,
                amount: $sourceAmount,
                balanceBefore: $fromBefore,
                balanceAfter: $fromAfter,
                createdBy: $createdBy,
                reference: $transfer,
                notes: $notes,
            );

            $toBefore = $toAccount->balance;

            $toAfter = $toBefore + $destinationAmount;

            $toAccount->balance = $toAfter;
            $toAccount->save();
            $this->recordTransaction(
                account: $toAccount,
                direction: TransactionDirection::IN,
                transactionType: $transactionType,
                amount: $destinationAmount,
                balanceBefore: $toBefore,
                balanceAfter: $toAfter,
                createdBy: $createdBy,
                reference: $transfer,
                notes: $notes
            );

            if (! $isProfitDistribution) {
                $profitBefore = $profitAccount->balance;
                if ($profitBefore < $profitDeduction) {
                    throw new RuntimeException(
                        __('services.capital_transfer.insufficient_profit_balance')
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
                    notes: $notes
                );
            }

            return $transfer;
        });

    }
}
