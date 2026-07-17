<?php

namespace App\Models;

use App\Enums\CurrencyType;
use App\Enums\FeeMode;
use App\Enums\PaymentStatus;
use App\Enums\ReceiverMethod;
use App\Enums\TransferCalculationMode;
use App\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transfer extends Model
{

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
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

    public function profitTransactions()
    {
        return $this->hasMany(ProfitAccountTransaction::class);
    }
    protected $fillable = [
        'receiver_name',
        'receiver_method',
        'requested_amount',
        'requested_currency',
        'fee_mode',
        'commission_amount',
        'commission_currency',
        'amount_due',
        'due_currency',
        'notes',
        'receiver_wallet_phone',
        'receiver_account_number',
        'amount',
        'currency',
        'received_by',
        'received_at',
        'customer_pay_currency',
        'customer_payable_amount',
        'customer_payable_currency',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'transfer_amount',
        'calculation_mode',
        'status',
        'completed_by',
        'completed_at',
        'transfer_proof_path',
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
            'received_at' => 'datetime',
            'receiver_method'=>ReceiverMethod::class,
            'commission_currency'=>CurrencyType::class,
            'payment_status' => PaymentStatus::class,
            'currency' => CurrencyType::class,
            'created_at' => 'datetime',
            'customer_payable_currency' => CurrencyType::class,
            'calculation_mode' => TransferCalculationMode::class,


        ];
    }
}
