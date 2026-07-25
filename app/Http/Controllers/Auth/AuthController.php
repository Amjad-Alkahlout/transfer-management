<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    public function logout(Request $request)
    {

        $locale = session('locale');

        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($locale) {
            $request->session()->put('locale', $locale);
        }

        return redirect()->route('login');
    }
}
