<?php

namespace Database\Seeders;

use App\Enums\CurrencyType;
use App\Models\ExchangeRate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{

    public function run(): void
    {
        ExchangeRate::upsert([
            [
                'currency' => CurrencyType::USD->value,
                'rate_to_usd' => 1.00000000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'currency' => CurrencyType::AED->value,
                'rate_to_usd' => 0.27229400,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'currency' => CurrencyType::ILS->value,
                'rate_to_usd' => 0.26954100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['currency'], ['rate_to_usd', 'updated_at']);
    }
}
