<?php

namespace App\Services;

use App\Enums\CurrencyType;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExchangeRateManagementService
{
    public function update(CurrencyType $currency, string $rate): ExchangeRate
    {
        return DB::transaction(function () use ($currency, $rate): ExchangeRate {
            $model = ExchangeRate::query()->where('currency', $currency)->lockForUpdate()->first();
            if (! $model) {
                throw ValidationException::withMessages(['currency' => 'The exchange rate does not exist.']);
            }
            $model->update(['rate_to_usd' => $rate]);
            return $model->refresh();
        });
    }
}
