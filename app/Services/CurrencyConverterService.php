<?php

namespace App\Services;

use App\Enums\CurrencyType;
use App\Models\ExchangeRate;
use Exception;

class CurrencyConverterService
{
    public function convert(
        float $amount,
        CurrencyType $from,
        CurrencyType $to
    ): float {

        // إذا كانت نفس العملة
        if ($from === $to) {
            return round($amount, 2);
        }

        $rates = ExchangeRate::whereIn('currency', [$from, $to])
            ->get()
            ->keyBy('currency');
        $fromRate = $rates[$from->value] ?? null;
        $toRate = $rates[$to->value] ?? null;

        if (!$fromRate || !$toRate) {
            throw new Exception('Exchange rate not found.');
        }

        // تحويل إلى الدولار
        $amountInUsd = $amount * $fromRate->rate_to_usd;

        // تحويل من الدولار إلى العملة المطلوبة
        $convertedAmount = $amountInUsd / $toRate->rate_to_usd;

        return round($convertedAmount, 2);
    }
}
