<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::livewire('/login', 'pages::auth.login')
    ->name('login')
    ->middleware('guest');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

Route::livewire('/bank-accounts', 'pages::bank-accounts.index')
    ->name('bank-accounts.index')
    ->middleware('auth')
    ->middleware('throttle:10,1');

Route::livewire('create-transfer', 'pages::transfers.create')
    ->name('create-transfer')
    ->middleware('auth')
    ->middleware('throttle:10,1');

Route::livewire('/transfers', 'pages::transfers.index')
    ->name('transfers.index')
    ->middleware('auth')
    ->middleware('throttle:10,1');


Route::livewire('/transfers/{transfer}/edit', 'pages::transfers.edit')
    ->name('transfers.edit')
    ->middleware('auth');

Route::livewire('/transfers/{transfer}', 'pages::transfers.show')
    ->name('transfers.show')
    ->middleware('auth');

Route::livewire('/transfers/{transfer}/receive-payment', 'transfers.receive-payment'
)->name('transfers.receive-payment')
    ->middleware('auth');

Route::livewire('/transfers/exchange-rates', 'exchange-rates.index')
    ->name('exchange-rates.index')
    ->middleware('auth');

Route::livewire('/transfers/commission-rules', 'commission-rules.index')
    ->name('commission-rules.index')
    ->middleware('auth');


Route::livewire('/dashboard', 'pages::dashboard.index')
    ->middleware('auth')
    ->name('dashboard');
