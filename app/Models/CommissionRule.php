<?php

namespace App\Models;

use App\Enums\CurrencyType;
use Illuminate\Database\Eloquent\Model;
// Commission amount is always stored in AED.
class CommissionRule extends Model
{
    protected $fillable = [
        'currency',
        'min_amount',
        'max_amount',
        'commission_amount',
    ];
    protected function casts(): array
    {
        return [
            'currency' => CurrencyType::class,
        ];
    }
}
