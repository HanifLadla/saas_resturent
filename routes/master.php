<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| QB Modern Restaurant System - Master Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/dashboard');
});

// Authentication Routes
Auth::routes();

// Include all module routes
require_once base_path('Modules/Dashboard/routes/web.php');
require_once base_path('Modules/POS/routes/web.php');
require_once base_path('Modules/Inventory/routes/web.php');
require_once base_path('Modules/Menu/routes/web.php');
require_once base_path('Modules/Customers/routes/web.php');
require_once base_path('Modules/Staff/routes/web.php');
require_once base_path('Modules/Suppliers/routes/web.php');
require_once base_path('Modules/Reports/routes/web.php');
require_once base_path('Modules/Subscriptions/routes/web.php');
require_once base_path('Modules/Notifications/routes/web.php');
require_once base_path('Modules/Accounting/routes/web.php');
require_once base_path('Modules/KitchenDisplay/routes/web.php');
require_once base_path('Modules/API/routes/web.php');