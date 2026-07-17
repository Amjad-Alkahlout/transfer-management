<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CapitalTransfer extends Model
{
    protected $fillable = [
        'from_account_id',
        'to_account_id',
        'source_amount',
        'destination_amount',
        'transfer_cost',
        'exchange_rate',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'source_amount' => 'decimal:2',
            'destination_amount' => 'decimal:2',
            'transfer_cost' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
        ];
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(CapitalAccount::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(CapitalAccount::class, 'to_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(CapitalTransaction::class, 'reference');
    }
}
