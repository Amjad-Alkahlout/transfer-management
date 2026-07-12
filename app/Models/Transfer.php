<?php

namespace App\Models;

use App\Enums\CurrencyType;
use App\Enums\FeeMode;
use App\Enums\ReceiverMethod;
use App\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfer extends Model
{
    public function account(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pricer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'priced_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
    protected $fillable = [
        'sender_name',
        'receiver_name',
        'receiver_method',
        'requested_amount',
        'requested_currency',
        'fee_mode',
        'exchange_rate',
        'commission_amount',
        'commission_currency',
        'amount_due',
        'due_currency',
        'bank_account_id',
        'notes',
        'receiver_wallet_phone',
        'receiver_account_number',
    ];

    protected function casts(): array
    {
        return [
            'fee_mode' =>FeeMode::class,
            'status' =>TransferStatus::class,
            'requested_currency'=>CurrencyType::class,
            'priced_at' => 'datetime',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'receiver_method'=>ReceiverMethod::class,
            'commission_currency'=>CurrencyType::class,

        ];
    }
}
