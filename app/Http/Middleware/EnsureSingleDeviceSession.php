<?php

namespace App\Http\Middleware;

use App\Enums\DeviceType;
use App\Models\DeviceSession;
use Closure;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleDeviceSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $agent = new Agent();
            $deviceType = ($agent->isMobile() || $agent->isTablet())
                ? DeviceType::MOBILE
                : DeviceType::DESKTOP;

            $currentSessionId = $request->session()->getId();

            $record = DeviceSession::query()
                ->where('user_id', auth()->id())
                ->where('device_type', $deviceType)
                ->first();

            if (! $record) {
                // ما في سجل أصلاً (أول مرور بعد نشر الميزة، أو تنظيف يدوي للجدول).
                // مش دليل تعارض، فما منطرد المستخدم — منسجله كأول جلسة رسمية له.
                DeviceSession::create([
                    'user_id' => auth()->id(),
                    'device_type' => $deviceType,
                    'session_id' => $currentSessionId,
                    'last_activity' => now(),
                ]);
            } elseif ($record->session_id !== $currentSessionId) {
                // هون بس التعارض الحقيقي: جهاز تاني فعلياً استولى على الجلسة
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->with('error', __('auth.errors.logged_in_elsewhere'));
            } else {
                if (! $record->last_activity || $record->last_activity->diffInMinutes(now()) >= 5) {
                    $record->update(['last_activity' => now()]);
                }
            }
        }

        return $next($request);
    }
}
