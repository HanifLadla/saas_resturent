<?php

use Illuminate\Support\Facades\Route;
use Modules\Customers\Controllers\CustomerController;

Route::middleware(['auth', 'restaurant.active'])->prefix('customers')->name('customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::post('/', [CustomerController::class, 'store'])->name('store');
    Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
    Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
    Route::get('/{customer}/orders', [CustomerController::class, 'orders'])->name('orders');
    Route::post('/{customer}/loyalty-points', [CustomerController::class, 'addLoyaltyPoints'])->name('loyalty.add');
    Route::post('/{customer}/redeem-points', [CustomerController::class, 'redeemLoyaltyPoints'])->name('loyalty.redeem');
});