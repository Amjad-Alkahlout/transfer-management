<?php

namespace App\Models;

use App\Enums\CapitalTransactionType;
use App\Enums\TransactionDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CapitalTransaction extends Model
{
    protected $fillable = [
        'capital_account_id',
        'amount',
        'direction',
        'transaction_type',
        'balance_before',
        'balance_after',
        'description',
        'notes',
        'created_by',
        'reference_type',
        'reference_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',

            'direction' => TransactionDirection::class,
            'transaction_type' => CapitalTransactionType::class,
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CapitalAccount::class, 'capital_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
