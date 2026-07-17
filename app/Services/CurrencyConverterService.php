<?php

namespace App\Services;

use App\Enums\CurrencyType;
use App\Models\ExchangeRate;
use Exception;

class CurrencyConverterService
{
    private function exchangeRate(
        CurrencyType $from,
        CurrencyType $to,
    ): float {

        if ($from === $to) {
            return 1;
        }

        $rates = ExchangeRate::whereIn(
            'currency',
            [$from->value, $to->value]
        )->get()->keyBy('currency');

        $fromRate = $rates[$from->value] ?? null;
        $toRate = $rates[$to->value] ?? null;

        if (! $fromRate || ! $toRate) {
            throw new Exception('Exchange rate not found.');
        }

        return $fromRate->rate_to_usd / $toRate->rate_to_usd;
    }

    public function convert(
        float $amount,
        CurrencyType $from,
        CurrencyType $to,
    ): float {

        return round(
            $amount * $this->exchangeRate($from, $to),
            2
        );
    }

    public function getExchangeRate(
        CurrencyType $from,
        CurrencyType $to,
    ): float {

        return round(
            $this->exchangeRate($from, $to),
            6
        );
    }
}
