<?php

namespace Database\Seeders;

use App\Enums\CurrencyType;
use App\Models\CommissionRule;
use Illuminate\Database\Seeder;

class ComissionRateSeeder extends Seeder
{
    public function run(): void
    {
        CommissionRule::upsert([

            // ILS
            [
                'currency' => CurrencyType::ILS->value,
                'min_amount' => 0,
                'max_amount' => 1000,
                'commission_amount' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'currency' => CurrencyType::ILS->value,
                'min_amount' => 1000.01,
                'max_amount' => 5000,
                'commission_amount' => 35,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // USD
            [
                'currency' => CurrencyType::USD->value,
                'min_amount' => 0,
                'max_amount' => 500,
                'commission_amount' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'currency' => CurrencyType::USD->value,
                'min_amount' => 500.01,
                'max_amount' => 5000,
                'commission_amount' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // AED
            [
                'currency' => CurrencyType::AED->value,
                'min_amount' => 0,
                'max_amount' => 1000,
                'commission_amount' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'currency' => CurrencyType::AED->value,
                'min_amount' => 1000.01,
                'max_amount' => 5000,
                'commission_amount' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ], ['currency', 'min_amount'], [
            'max_amount',
            'commission_amount',
            'updated_at',
        ]);
    }
}
