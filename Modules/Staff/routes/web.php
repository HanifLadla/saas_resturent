<?php

use Illuminate\Support\Facades\Route;
use Modules\Staff\Controllers\StaffController;

Route::middleware(['auth', 'restaurant.active'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/', [StaffController::class, 'index'])->name('index');
    Route::post('/', [StaffController::class, 'store'])->name('store');
    Route::put('/{user}', [StaffController::class, 'update'])->name('update');
    Route::patch('/{user}/status', [StaffController::class, 'updateStatus'])->name('status');
    Route::post('/{user}/reset-password', [StaffController::class, 'resetPassword'])->name('reset-password');
    Route::get('/roles-permissions', [StaffController::class, 'rolesPermissions'])->name('roles');
    Route::post('/{user}/permissions', [StaffController::class, 'updatePermissions'])->name('permissions');
});