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

    public function calculateFromReceiverAmount(
        float $requestedAmount,
        CurrencyType $requestedCurrency,
        CurrencyType $payCurrency,
        FeeMode $feeMode,
    ): array {

        $commissionInOperationCurrency = $this->commissionService->getCommission(
            $requestedAmount,
            $requestedCurrency,
        );

        $commissionRule = $this->commissionService->getCommissionRule(
            $requestedAmount,
            $requestedCurrency,
        );

        if (
            $feeMode === FeeMode::INCLUDED &&
            $commissionInOperationCurrency >= $requestedAmount
        ) {
            throw new \InvalidArgumentException(
                __('services.transfer_calculator.included_fee_exceeds_amount')
            );
        }

        if ($feeMode === FeeMode::INCLUDED) {

            $transferAmount = $requestedAmount - $commissionInOperationCurrency;

            $customerPayableBaseAmount = $requestedAmount;

        } else {

            $transferAmount = $requestedAmount;

            $customerPayableBaseAmount = $requestedAmount + $commissionInOperationCurrency;
        }

        $customerPayableAmount = $this->converter->convert(
            $customerPayableBaseAmount,
            $requestedCurrency,
            $payCurrency
        );

        return [
            'transfer_amount' => round($transferAmount, 2),

            'customer_payable_amount' => round($customerPayableAmount, 2),
            'customer_payable_currency' => $payCurrency,

            // Stored in DB (always AED)
            'commission_amount' => round($commissionRule->commission_amount, 2),
            'commission_currency' => CurrencyType::AED,
        ];
    }

    public function calculateFromCustomerPayment(
        float $customerPayableAmount,
        CurrencyType $payCurrency,
        CurrencyType $requestedCurrency,
    ): array {

        $commissionInOperationCurrency = $this->commissionService->getCommission(
            $customerPayableAmount,
            $payCurrency,
        );

        $commissionRule = $this->commissionService->getCommissionRule(
            $customerPayableAmount,
            $payCurrency,
        );

        if ($commissionInOperationCurrency >= $customerPayableAmount) {
            throw new \InvalidArgumentException(
                __('services.transfer_calculator.fee_exceeds_amount')
            );
        }

        $amountAfterCommission = $customerPayableAmount - $commissionInOperationCurrency;

        $requestedAmount = $this->converter->convert(
            $amountAfterCommission,
            $payCurrency,
            $requestedCurrency
        );

        return [
            'requested_amount' => round($requestedAmount, 2),
            'requested_currency' => $requestedCurrency,

            'transfer_amount' => round($requestedAmount, 2),

            'customer_payable_amount' => round($customerPayableAmount, 2),
            'customer_payable_currency' => $payCurrency,

            // Stored in DB (always AED)
            'commission_amount' => round($commissionRule->commission_amount, 2),
            'commission_currency' => CurrencyType::AED,
        ];
    }
}
