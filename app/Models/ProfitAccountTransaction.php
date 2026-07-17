<?php

namespace App\Models;

use App\Enums\CurrencyType;
use Illuminate\Database\Eloquent\Model;

class ProfitAccountTransaction extends Model
{
    protected $fillable = [
        'transfer_id',
        'amount',
        'currency',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'currency' => CurrencyType::class,
        ];
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
