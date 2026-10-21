<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CapitalAccountController;
use App\Http\Controllers\Api\V1\CapitalTransactionController;
use App\Http\Controllers\Api\V1\CapitalTransferController;
use App\Http\Controllers\Api\V1\DashboardSummaryController;
use App\Http\Controllers\Api\V1\ExchangeRateController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\TransferCalculationController;
use App\Http\Controllers\Api\V1\TransferController;
use App\Http\Controllers\Api\V1\TransferMutationController;
use App\Http\Controllers\Api\V1\TransferActionController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'api.active'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->middleware('throttle:10,1');

        Route::get('/me', [AuthController::class, 'me']);

        Route::middleware('can:view-transfers')->group(function (): void {
            Route::get('/transfers', [TransferController::class, 'index']);
            Route::get('/transfers/{transfer}', [TransferController::class, 'show']);
        });

        Route::post('/transfers/calculate', TransferCalculationController::class)
            ->middleware(['can:create-transfer', 'throttle:30,1']);
        Route::post('/transfers', [TransferMutationController::class, 'store'])
            ->middleware(['can:create-transfer', 'throttle:10,1']);
        Route::patch('/transfers/{transfer}', [TransferMutationController::class, 'update'])
            ->middleware(['can:update-transfer', 'throttle:10,1']);

        Route::post('/transfers/{transfer}/payments', [PaymentController::class, 'store'])
            ->middleware(['can:receive-payment', 'throttle:10,1']);
        Route::post('/transfers/{transfer}/execute', [TransferActionController::class, 'execute'])
            ->middleware(['can:execute-transfer', 'throttle:10,1']);
        Route::post('/transfers/{transfer}/cancel', [TransferActionController::class, 'cancel'])
            ->middleware(['can:cancel-transfer', 'throttle:10,1']);
        Route::post('/transfers/{transfer}/force-cancel', [TransferActionController::class, 'forceCancel'])
            ->middleware(['can:force-cancel-completed-transfer', 'throttle:5,1']);

        Route::middleware('can:view-financial-dashboard')->group(function (): void {
            Route::get('/dashboard/summary', DashboardSummaryController::class);
            Route::get('/exchange-rates', [ExchangeRateController::class, 'index']);
        });

        Route::put('/exchange-rates/{currency}', [ExchangeRateController::class, 'update'])
            ->middleware(['can:manage-exchange-rates', 'throttle:10,1']);

        Route::middleware('can:view-capital-accounts')->group(function (): void {
            Route::get('/capital-accounts', [CapitalAccountController::class, 'index']);
        });
        Route::middleware('can:manage-capital-accounts')->group(function (): void {
            Route::post('/capital-accounts', [CapitalAccountController::class, 'store'])->middleware('throttle:10,1');
            Route::patch('/capital-accounts/{account}', [CapitalAccountController::class, 'update'])->middleware('throttle:10,1');
            Route::post('/capital-accounts/{account}/adjustments', [CapitalAccountController::class, 'adjust'])->middleware('throttle:10,1');
            Route::post('/capital-accounts/{account}/profit-withdrawals', [CapitalAccountController::class, 'withdraw'])->middleware('throttle:10,1');
        });
        Route::middleware('can:view-capital-transfers')->group(function (): void {
            Route::get('/capital-transfers', [CapitalTransferController::class, 'index']);
        });
        Route::middleware('can:create-capital-transfer')->group(function (): void {
            Route::post('/capital-transfers/preview', [CapitalTransferController::class, 'preview'])->middleware('throttle:30,1');
            Route::post('/capital-transfers', [CapitalTransferController::class, 'store'])->middleware('throttle:10,1');
        });
        Route::get('/capital-transactions', [CapitalTransactionController::class, 'index'])->middleware('can:view-capital-ledger');
        Route::middleware('can:manage-users')->prefix('users')->group(function (): void {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store'])->middleware('throttle:10,1');
            Route::get('/{user}', [UserController::class, 'show']);
            Route::patch('/{user}', [UserController::class, 'update'])->middleware('throttle:10,1');
            Route::put('/{user}/password', [UserController::class, 'password'])->middleware('throttle:5,1');
            Route::delete('/{user}', [UserController::class, 'destroy'])->middleware('throttle:5,1');
        });
    });
});
