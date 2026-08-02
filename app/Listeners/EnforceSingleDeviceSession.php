<?php

namespace App\Listeners;

use App\Enums\DeviceType;
use App\Models\DeviceSession;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

class EnforceSingleDeviceSession
{
    public function handle(Login $event): void
    {
        $agent = new Agent();
        $deviceType = ($agent->isMobile() || $agent->isTablet())
            ? DeviceType::MOBILE
            : DeviceType::DESKTOP;

        $currentSessionId = session()->getId();

        $existing = DeviceSession::query()
            ->where('user_id', $event->user->id)
            ->where('device_type', $deviceType)
            ->first();

        if ($existing && $existing->session_id !== $currentSessionId) {
            DB::table('sessions')->where('id', $existing->session_id)->delete();
        }

        DeviceSession::updateOrCreate(
            ['user_id' => $event->user->id, 'device_type' => $deviceType],
            ['session_id' => $currentSessionId, 'last_activity' => now()]
        );
    }
}
