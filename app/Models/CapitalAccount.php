<?php

namespace App\Models;

use App\Enums\Branch;
use App\Enums\CapitalAccountType;
use App\Enums\CurrencyType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CapitalAccount extends Model
{
    protected $fillable = [
        'name',
        'branch',
        'currency',
        'account_type',
        'balance',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'branch' => Branch::class,
            'currency' => CurrencyType::class,
            'account_type'=> CapitalAccountType::class,
            'balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CapitalTransaction::class);
    }
}
