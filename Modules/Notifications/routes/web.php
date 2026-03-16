<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Controllers\NotificationController;

Route::middleware(['auth', 'restaurant.active'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/send', [NotificationController::class, 'sendCustomNotification'])->name('send');
    Route::post('/send-bulk', [NotificationController::class, 'sendBulkNotification'])->name('send-bulk');
    Route::get('/history', [NotificationController::class, 'history'])->name('history');
});