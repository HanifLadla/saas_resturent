<?php

use Illuminate\Support\Facades\Route;
use Modules\Menu\Controllers\MenuController;

Route::middleware(['auth', 'restaurant.active'])->prefix('menu')->name('menu.')->group(function () {
    Route::get('/', [MenuController::class, 'index'])->name('index');
    Route::get('/categories', [MenuController::class, 'categories'])->name('categories');
    Route::post('/categories', [MenuController::class, 'storeCategory'])->name('categories.store');
    Route::put('/categories/{category}', [MenuController::class, 'updateCategory'])->name('categories.update');
    Route::get('/products', [MenuController::class, 'products'])->name('products');
    Route::post('/products', [MenuController::class, 'storeProduct'])->name('products.store');
    Route::put('/products/{product}', [MenuController::class, 'updateProduct'])->name('products.update');
    Route::delete('/products/{product}', [MenuController::class, 'deleteProduct'])->name('products.delete');
});