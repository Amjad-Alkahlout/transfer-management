<?php

namespace App\Models;

use App\Enums\DeviceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'device_type', 'session_id', 'last_activity'])]
class DeviceSession extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'device_type' => DeviceType::class,
            'last_activity' => 'datetime',
        ];
    }
}
