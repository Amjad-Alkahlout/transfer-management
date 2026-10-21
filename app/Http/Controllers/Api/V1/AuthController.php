<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password) || ! $user->is_active) {
            return response()->json([
                'message' => 'The provided credentials are invalid.',
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $user->createToken($validated['device_name'])->plainTextToken;

        return response()->json([
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => now()->addMinutes((int) config('sanctum.expiration'))->toIso8601String(),
                'user' => UserResource::make($user)->resolve($request),
            ],
        ], Response::HTTP_CREATED);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'data' => [
                'message' => 'Logged out successfully.',
            ],
        ]);
    }

    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }
}
