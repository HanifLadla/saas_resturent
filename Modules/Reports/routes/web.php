<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Controllers\ReportController;

Route::middleware(['auth', 'restaurant.active'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/sales', [ReportController::class, 'salesReport'])->name('sales');
    Route::get('/inventory', [ReportController::class, 'inventoryReport'])->name('inventory');
    Route::get('/financial', [ReportController::class, 'financialReport'])->name('financial');
    Route::post('/export', [ReportController::class, 'export'])->name('export');
});