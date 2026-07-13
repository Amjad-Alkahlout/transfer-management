<?php

namespace App\Models;

use App\Enums\CurrencyType;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'currency',
        'rate_to_usd',
    ];

    protected function casts(): array
    {
        return [
            'currency' => CurrencyType::class,
        ];
    }
}
