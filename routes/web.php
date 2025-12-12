<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Home page
Route::get('/', [HomeController::class, 'index'])->name('home');

// Plans - accessible by everyone
Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Subscription routes - require authentication
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

    Route::post('/subscriptions/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscriptions.web.subscribe');
    Route::put('/subscriptions/{id}/upgrade', [SubscriptionController::class, 'upgrade'])->name('subscriptions.web.upgrade');
    Route::put('/subscriptions/{id}/downgrade', [SubscriptionController::class, 'downgrade'])->name('subscriptions.web.downgrade');
    Route::delete('/subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.web.cancel');
});
