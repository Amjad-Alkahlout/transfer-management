<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;

class LocalTime
{
    public static function format(?Carbon $date, string $format = 'd/m/Y H:i', ?User $forUser = null): ?string
    {
        if (! $date) {
            return null;
        }

        $user = $forUser ?? auth()->user();
        $timezone = $user?->timezone ?? config('app.timezone');

        return $date->clone()->setTimezone($timezone)->format($format);
    }
}
