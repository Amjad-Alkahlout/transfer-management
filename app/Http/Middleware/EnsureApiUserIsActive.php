<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_active) {
            return response()->json([
                'message' => 'This account is inactive.',
                'error' => [
                    'code' => 'INACTIVE_USER',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
