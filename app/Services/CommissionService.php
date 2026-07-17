<?php

namespace App\Services;

use App\Enums\CurrencyType;
use App\Models\CommissionRule;
use Exception;

class CommissionService
{
    public function __construct(
        protected CurrencyConverterService $converter,
    ) {
    }

    public function getCommissionRule(
        float $amount,
        CurrencyType $currency,
    ): CommissionRule
    {
        $rule = CommissionRule::where('currency', $currency)
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->first();

        if (! $rule) {
            throw new Exception('No commission rule found for this amount.');
        }

        return $rule;
    }

    public function getCommission(
        float $amount,
        CurrencyType $currency,
    ): float {

        $rule = CommissionRule::where('currency', $currency)
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->first();

        if (! $rule) {
            throw new Exception('No commission rule found for this amount.');
        }

        return round(
            $this->converter->convert(
                $rule->commission_amount,
                CurrencyType::AED,
                $currency,
            ),
            2
        );
    }
}
