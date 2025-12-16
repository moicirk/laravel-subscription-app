<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/websocket', [UserController::class, 'index']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Subscription routes
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('/current', [SubscriptionController::class, 'current'])->name('subscriptions.current');
        Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
        Route::post('/{id}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::post('/{id}/renew', [SubscriptionController::class, 'renew'])->name('subscriptions.renew');
    });
});
