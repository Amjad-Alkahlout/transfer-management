<?php

namespace App\Services;

use App\Enums\CurrencyType;
use App\Enums\FeeMode;

class TransferCalculatorService
{
    public function __construct(
        protected CurrencyConverterService $converter,
        protected CommissionService $commissionService,
    ) {}

    public function calculate(
        float $requestedAmount,
        CurrencyType $requestedCurrency,
        CurrencyType $payCurrency,
        FeeMode $feeMode,
    ): array {

        $commissionAmount = $this->commissionService->getCommission(
            $requestedAmount,
            $requestedCurrency,
            $feeMode
        );
        if (
            $feeMode === FeeMode::INCLUDED &&
            $commissionAmount >= $requestedAmount
        ) {
            throw new \Exception(
                'Commission cannot be greater than or equal to the transfer amount.'
            );
        }
        if ($feeMode === FeeMode::EXCLUDED) {
            $amountToConvert = $requestedAmount + $commissionAmount;
        } else {
            $amountToConvert = $requestedAmount - $commissionAmount;
        }

        $customerPayableAmount = $this->converter->convert(
            $amountToConvert,
            $requestedCurrency,
            $payCurrency
        );

        return [
            'customer_payable_amount' => round($customerPayableAmount, 2),

            'customer_payable_currency' => $payCurrency->value,

            'commission_amount' => round($commissionAmount, 2),

            'commission_currency' => $requestedCurrency->value,
        ];
    }
}
