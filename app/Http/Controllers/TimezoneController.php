<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TimezoneController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'timezone' => ['required', 'string', 'timezone:all_with_bc'],
        ]);

        if (! auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        auth()->user()->update([
            'timezone' => $validated['timezone'],
        ]);

        return response()->noContent();
    }
}
