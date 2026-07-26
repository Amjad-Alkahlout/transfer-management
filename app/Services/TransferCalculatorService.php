<?php

namespace App\Services;

use App\Enums\CurrencyType;


class TransferCalculatorService
{
    public function __construct(
        protected CurrencyConverterService $converter,
    ) {}

    public function calculateFromReceiverAmount(
        float $requestedAmount,
        CurrencyType $requestedCurrency,
        CurrencyType $payCurrency,
        float $commissionAmount,
    ): array {

        $baseCustomerPayable = $this->converter->convert(
            $requestedAmount,
            $requestedCurrency,
            $payCurrency,
        );

        $commissionInPayCurrency = $this->converter->convert(
            $commissionAmount,
            CurrencyType::AED,
            $payCurrency,
        );

        $customerPayable = $baseCustomerPayable + $commissionInPayCurrency;

        return [
            'transfer_amount' => round($requestedAmount, 2),

            'customer_payable_amount' => round($customerPayable, 2),
            'customer_payable_currency' => $payCurrency,

            'commission_amount' => round($commissionAmount, 2),
            'commission_currency' => CurrencyType::AED,
        ];
    }

    public function calculateFromCustomerPayment(
        float $customerPayableAmount,
        CurrencyType $payCurrency,
        CurrencyType $requestedCurrency,
        float $commissionAmount,
    ): array {

        $commissionInPayCurrency = $this->converter->convert(
            $commissionAmount,
            CurrencyType::AED,
            $payCurrency,
        );

        if ($commissionInPayCurrency >= $customerPayableAmount) {
            throw new \InvalidArgumentException(
                __('services.transfer_calculator.fee_exceeds_amount')
            );
        }

        $baseCustomerPayable = $customerPayableAmount - $commissionInPayCurrency;

        $requestedAmount = $this->converter->convert(
            $baseCustomerPayable,
            $payCurrency,
            $requestedCurrency,
        );

        return [
            'requested_amount' => round($requestedAmount, 2),
            'requested_currency' => $requestedCurrency,

            'transfer_amount' => round($requestedAmount, 2),

            'customer_payable_amount' => round($customerPayableAmount, 2),
            'customer_payable_currency' => $payCurrency,

            'commission_amount' => round($commissionAmount, 2),
            'commission_currency' => CurrencyType::AED,
        ];
    }
}
