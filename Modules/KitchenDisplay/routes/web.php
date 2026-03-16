<?php

use Illuminate\Support\Facades\Route;
use Modules\KitchenDisplay\Controllers\KitchenDisplayController;

Route::middleware(['auth', 'restaurant.active'])->prefix('kitchen')->name('kitchen.')->group(function () {
    Route::get('/display', [KitchenDisplayController::class, 'display'])->name('display');
    Route::get('/display/data', [KitchenDisplayController::class, 'getDisplayData'])->name('display.data');
    Route::patch('/orders/{kitchenOrder}/status', [KitchenDisplayController::class, 'updateStatus'])->name('orders.status');
});