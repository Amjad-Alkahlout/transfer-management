<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use HasFactory;
    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'bank_account_id');
    }
    protected $fillable = ['owner_name', 'label', 'bank_name' ,'account_number', 'currency', 'is_active', 'notes'];
}
