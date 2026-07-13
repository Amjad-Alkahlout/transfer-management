<?php

namespace App\Services;

use App\Enums\CurrencyType;
use App\Enums\FeeMode;
use App\Models\CommissionRule;
use Exception;

class CommissionService
{
    public function getCommission(
        float $amount,
        CurrencyType $currency,
    ): float {

        $rule = CommissionRule::where('currency', $currency)
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->first();

        if (!$rule) {
            throw new Exception('No commission rule found for this amount.');
        }

        return (float) $rule->commission_amount;
    }
}
