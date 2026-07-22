<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\UserRole;

#[Fillable(['name', 'email', 'password','telegram_chat_id', 'telegram_notifications_enabled','telegram_link_code',
    'telegram_link_expires_at',])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;


    public function createdTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'created_by');
    }

    public function pricedTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'priced_by');
    }

    public function completedTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'completed_by');
    }

    public function cancelledTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'cancelled_by');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'telegram_link_expires_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isCoordinator(): bool
    {
        return $this->role === UserRole::COORDINATOR;
    }

    public function isExecutor(): bool
    {
        return $this->role === UserRole::EXECUTOR;
    }

    public function isTransferExecutor(): bool
    {
        return $this->role === UserRole::TRANSFER_EXECUTOR;
    }
}
