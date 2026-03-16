<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Controllers\AccountingController;

Route::middleware(['auth', 'restaurant.active'])->prefix('accounting')->name('accounting.')->group(function () {
    Route::get('/', [AccountingController::class, 'index'])->name('index');
    Route::get('/chart-of-accounts', [AccountingController::class, 'chartOfAccounts'])->name('chart-of-accounts');
    Route::post('/chart-of-accounts', [AccountingController::class, 'createAccount'])->name('accounts.create');
    Route::get('/journal-entries', [AccountingController::class, 'journalEntries'])->name('journal-entries');
    Route::post('/journal-entries', [AccountingController::class, 'createJournalEntry'])->name('journal-entries.create');
    Route::get('/trial-balance', [AccountingController::class, 'trialBalance'])->name('trial-balance');
    Route::get('/balance-sheet', [AccountingController::class, 'balanceSheet'])->name('balance-sheet');
    Route::get('/profit-loss', [AccountingController::class, 'profitLoss'])->name('profit-loss');
});