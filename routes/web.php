<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Modules\POS\Controllers\POSController;
use Modules\Dashboard\Controllers\DashboardController;
use Modules\Inventory\Controllers\InventoryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Auth Routes
Route::get('/', function () {
    return redirect('/login');
});
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes (Require Authentication)
Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // POS System
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [POSController::class, 'index'])->name('index');
        Route::post('/orders', [POSController::class, 'createOrder'])->name('orders.create');
        Route::patch('/orders/{order}/status', [POSController::class, 'updateOrderStatus'])->name('orders.status');
        Route::post('/orders/{order}/payment', [POSController::class, 'processPayment'])->name('orders.payment');
        Route::get('/products/search', [POSController::class, 'searchProducts'])->name('products.search');
        Route::get('/customers/search', [POSController::class, 'searchCustomers'])->name('customers.search');
        Route::get('/orders/{order}/receipt', [POSController::class, 'printReceipt'])->name('orders.receipt');
    });
    
    // Kitchen Display System
    Route::prefix('kitchen')->name('kitchen.')->group(function () {
        Route::get('/display', [KitchenDisplayController::class, 'display'])->name('display');
        Route::get('/display/data', [KitchenDisplayController::class, 'getDisplayData'])->name('display.data');
        Route::patch('/orders/{kitchenOrder}/status', [KitchenDisplayController::class, 'updateStatus'])->name('orders.status');
    });
    
    // Inventory Management
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/items', [InventoryController::class, 'items'])->name('items');
        Route::post('/items', [InventoryController::class, 'storeItem'])->name('items.store');
        Route::put('/items/{item}', [InventoryController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{item}', [InventoryController::class, 'deleteItem'])->name('items.delete');
        
        // Purchase Orders
        Route::get('/purchase-orders', [InventoryController::class, 'purchaseOrders'])->name('purchase-orders');
        Route::post('/purchase-orders', [InventoryController::class, 'createPurchaseOrder'])->name('purchase-orders.create');
        Route::patch('/purchase-orders/{purchaseOrder}/receive', [InventoryController::class, 'receivePurchaseOrder'])->name('purchase-orders.receive');
        
        // Stock Adjustments
        Route::post('/stock-adjustments', [InventoryController::class, 'adjustStock'])->name('stock.adjust');
        Route::get('/low-stock-alerts', [InventoryController::class, 'lowStockAlerts'])->name('low-stock');
    });
    
    // Menu Management
    Route::prefix('menu')->name('menu.')->group(function () {
        Route::get('/', [MenuController::class, 'index'])->name('index');
        Route::get('/categories', [MenuController::class, 'categories'])->name('categories');
        Route::post('/categories', [MenuController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [MenuController::class, 'updateCategory'])->name('categories.update');
        
        Route::get('/products', [MenuController::class, 'products'])->name('products');
        Route::post('/products', [MenuController::class, 'storeProduct'])->name('products.store');
        Route::put('/products/{product}', [MenuController::class, 'updateProduct'])->name('products.update');
        Route::delete('/products/{product}', [MenuController::class, 'deleteProduct'])->name('products.delete');
    });
    
    // Customer Management
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        Route::get('/{customer}/orders', [CustomerController::class, 'orders'])->name('orders');
        Route::post('/{customer}/loyalty-points', [CustomerController::class, 'addLoyaltyPoints'])->name('loyalty.add');
    });
    
    // Staff Management
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/', [StaffController::class, 'index'])->name('index');
        Route::post('/', [StaffController::class, 'store'])->name('store');
        Route::put('/{user}', [StaffController::class, 'update'])->name('update');
        Route::patch('/{user}/status', [StaffController::class, 'updateStatus'])->name('status');
        Route::get('/roles-permissions', [StaffController::class, 'rolesPermissions'])->name('roles');
    });
    
    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('/financial', [ReportController::class, 'financial'])->name('financial');
        Route::post('/export', [ReportController::class, 'export'])->name('export');
    });
    
    // Accounting
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/', [AccountingController::class, 'index'])->name('index');
        Route::get('/chart-of-accounts', [AccountingController::class, 'chartOfAccounts'])->name('chart-of-accounts');
        Route::post('/chart-of-accounts', [AccountingController::class, 'createAccount'])->name('accounts.create');
        
        Route::get('/journal-entries', [AccountingController::class, 'journalEntries'])->name('journal-entries');
        Route::post('/journal-entries', [AccountingController::class, 'createJournalEntry'])->name('journal-entries.create');
        Route::patch('/journal-entries/{entry}/post', [AccountingController::class, 'postJournalEntry'])->name('journal-entries.post');
        
        Route::get('/trial-balance', [AccountingController::class, 'trialBalance'])->name('trial-balance');
        Route::get('/balance-sheet', [AccountingController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/profit-loss', [AccountingController::class, 'profitLoss'])->name('profit-loss');
    });
    
    // Global Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/update', [SettingsController::class, 'update'])->name('update');
        Route::get('/category/{category}', [SettingsController::class, 'getCategory'])->name('category');
        Route::post('/reset-defaults', [SettingsController::class, 'resetDefaults'])->name('reset-defaults');
    });
    
    // Subscriptions (Restaurant Admin only)
    Route::middleware(['role:restaurant_admin'])->prefix('subscription')->name('subscription.')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/plans', [SubscriptionController::class, 'plans'])->name('plans');
        Route::post('/upgrade', [SubscriptionController::class, 'upgrade'])->name('upgrade');
        Route::get('/billing-history', [SubscriptionController::class, 'billingHistory'])->name('billing');
    });
});

// API Routes
Route::prefix('api/v1')->middleware(['auth:sanctum', 'restaurant.active'])->group(function () {
    Route::apiResource('orders', OrderApiController::class);
    Route::apiResource('products', ProductApiController::class);
    Route::apiResource('customers', CustomerApiController::class);
    Route::apiResource('inventory', InventoryApiController::class);
    
    // Real-time endpoints
    Route::get('/kitchen/orders', [KitchenApiController::class, 'getOrders']);
    Route::patch('/kitchen/orders/{order}/status', [KitchenApiController::class, 'updateStatus']);
    Route::get('/pos/live-data', [POSApiController::class, 'getLiveData']);
});

// Super Admin Routes
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('restaurants', RestaurantController::class);
    Route::resource('subscription-plans', SubscriptionPlanController::class);
    Route::get('/system-settings', [AdminController::class, 'systemSettings'])->name('system-settings');
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
});