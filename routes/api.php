<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'api.active'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->middleware('throttle:10,1');

        Route::get('/me', [AuthController::class, 'me']);
    });
});
