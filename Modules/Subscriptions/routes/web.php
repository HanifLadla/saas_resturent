<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscriptions\Controllers\SubscriptionController;

Route::middleware(['auth', 'restaurant.active', 'role:restaurant_admin'])->prefix('subscription')->name('subscription.')->group(function () {
    Route::get('/', [SubscriptionController::class, 'index'])->name('index');
    Route::get('/plans', [SubscriptionController::class, 'plans'])->name('plans');
    Route::post('/upgrade', [SubscriptionController::class, 'upgrade'])->name('upgrade');
    Route::get('/billing-history', [SubscriptionController::class, 'billingHistory'])->name('billing');
});