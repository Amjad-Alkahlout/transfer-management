<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::livewire('/login', 'pages::auth.login')
    ->name('login')
    ->middleware('guest');

Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->name('telegram.webhook');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');


    Route::livewire('/capital-accounts', 'pages::capital-accounts.index')
        ->name('capital-accounts.index')
        ->middleware([
            'throttle:10,1',
            'can:view-capital-accounts',
        ]);

    Route::livewire('/transfers/create', 'pages::transfers.create')
        ->name('transfers.create')
        ->middleware([
            'throttle:10,1',
            'can:create-transfer',
        ]);

    Route::livewire('/transfers', 'pages::transfers.index')
        ->name('transfers.index')
        ->middleware('throttle:10,1');


    Route::livewire('/transfers/{transfer}/edit', 'pages::transfers.edit')
        ->name('transfers.edit')
    ->middleware(['throttle:10,1','can:update-transfer']);


    Route::livewire('/transfers/{transfer}', 'pages::transfers.show')
        ->name('transfers.show')
        ->middleware('can:view-transfers');


    Route::livewire('/transfers/{transfer}/receive-payment', 'transfers.receive-payment'
    )->name('transfers.receive-payment')
        ->middleware([
            'throttle:10,1',
            'can:receive-payment',
        ]);


    Route::livewire('/exchange-rates', 'exchange-rates.index')
        ->name('exchange-rates.index')
        ->middleware('can:manage-exchange-rates');

    Route::livewire('/capital-transfers', 'capital-transfers.index')
        ->name('capital-transfers.index')
        ->middleware('can:view-capital-transfers');

    Route::livewire('/commission-rules', 'commission-rules.index')
        ->name('commission-rules.index')
        ->middleware('can:manage-commission-rules');



    Route::livewire('/dashboard', 'pages::dashboard.index')
        ->name('dashboard');
});

