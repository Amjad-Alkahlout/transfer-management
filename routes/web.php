<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::livewire('/login', 'pages::auth.login')
    ->name('login')
    ->middleware('guest');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');


    Route::livewire('/bank-accounts', 'pages::bank-accounts.index')
        ->name('bank-accounts.index')
        ->middleware('throttle:10,1');

    Route::livewire('/transfers/create', 'pages::transfers.create')
        ->name('transfers.create')
        ->middleware('throttle:10,1');

    Route::livewire('/transfers', 'pages::transfers.index')
        ->name('transfers.index')
        ->middleware('throttle:10,1');


    Route::livewire('/transfers/{transfer}/edit', 'pages::transfers.edit')
        ->name('transfers.edit')
    ->middleware('throttle:10,1');


    Route::livewire('/transfers/{transfer}', 'pages::transfers.show')
        ->name('transfers.show');


    Route::livewire('/transfers/{transfer}/receive-payment', 'transfers.receive-payment'
    )->name('transfers.receive-payment')
    ->middleware('throttle:10,1');


    Route::livewire('/exchange-rates', 'exchange-rates.index')
        ->name('exchange-rates.index');


    Route::livewire('/commission-rules', 'commission-rules.index')
        ->name('commission-rules.index');



    Route::livewire('/dashboard', 'pages::dashboard.index')
        ->name('dashboard');
});

