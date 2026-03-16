<?php

use Illuminate\Support\Facades\Route;
use Modules\API\Controllers\APIController;

Route::prefix('api/v1')->middleware(['auth:sanctum', 'restaurant.active'])->group(function () {
    Route::get('/orders', [APIController::class, 'getOrders']);
    Route::post('/orders', [APIController::class, 'createOrder']);
    Route::get('/products', [APIController::class, 'getProducts']);
    Route::get('/customers', [APIController::class, 'getCustomers']);
    Route::get('/inventory', [APIController::class, 'getInventory']);
    Route::get('/analytics/sales', [APIController::class, 'getSalesAnalytics']);
    Route::get('/status', [APIController::class, 'status']);
});

Route::post('/webhooks/restaurant-system', [APIController::class, 'webhook']);