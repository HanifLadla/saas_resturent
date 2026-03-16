<?php

use Illuminate\Support\Facades\Route;
use Modules\POS\Controllers\POSController;

Route::middleware(['auth', 'restaurant.active'])->prefix('pos')->name('pos.')->group(function () {
    Route::get('/', [POSController::class, 'index'])->name('index');
    Route::post('/orders', [POSController::class, 'createOrder'])->name('orders.create');
    Route::patch('/orders/{order}/status', [POSController::class, 'updateOrderStatus'])->name('orders.status');
    Route::post('/orders/{order}/payment', [POSController::class, 'processPayment'])->name('orders.payment');
    Route::get('/products/search', [POSController::class, 'searchProducts'])->name('products.search');
    Route::get('/customers/search', [POSController::class, 'searchCustomers'])->name('customers.search');
});