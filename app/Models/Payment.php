<?php

namespace App\Models;

use App\Enums\CurrencyType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
    protected $fillable = [
        'transfer_id',
        'amount',
        'currency',
        'notes',
        'received_by',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'currency' => CurrencyType::class,
            'received_at' => 'datetime',
        ];
    }
}
