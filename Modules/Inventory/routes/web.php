<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Controllers\InventoryController;

Route::middleware(['auth', 'restaurant.active'])->prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::get('/items', [InventoryController::class, 'items'])->name('items');
    Route::post('/items', [InventoryController::class, 'storeItem'])->name('items.store');
    Route::put('/items/{item}', [InventoryController::class, 'updateItem'])->name('items.update');
    Route::post('/stock-adjustments', [InventoryController::class, 'adjustStock'])->name('stock.adjust');
    Route::get('/purchase-orders', [InventoryController::class, 'purchaseOrders'])->name('purchase-orders');
    Route::post('/purchase-orders', [InventoryController::class, 'createPurchaseOrder'])->name('purchase-orders.create');
});