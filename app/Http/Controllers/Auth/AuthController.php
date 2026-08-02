<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DeviceSession;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    public function logout(Request $request)
    {

        $locale = session('locale');

        DeviceSession::query()
            ->where('user_id', auth()->id())
            ->where('session_id', $request->session()->getId())
            ->delete();

        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($locale) {
            $request->session()->put('locale', $locale);
        }

        return redirect()->route('login');
    }
}
