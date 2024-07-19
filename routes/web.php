<?php

use FriendsOfBotble\Coinbase\Http\Controllers\CoinbaseController;
use Illuminate\Support\Facades\Route;

Route::middleware('core')
    ->prefix('payment/coinbase')
    ->name('payments.coinbase.')
    ->group(function () {
        Route::get('success', [CoinbaseController::class, 'success'])
            ->middleware('web')
            ->name('success');

        Route::get('error', [CoinbaseController::class, 'error'])
            ->middleware('web')
            ->name('error');

        Route::post('webhook', [CoinbaseController::class, 'webhook'])
            ->name('webhook');
    });
