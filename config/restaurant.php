<?php

return [
    /*
    |--------------------------------------------------------------------------
    | QB Modern Restaurant System Configuration
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'QB Modern Restaurant System'),
    'version' => '1.0.0',
    'modules' => [
        'Dashboard' => [
            'enabled' => true,
            'route_prefix' => 'dashboard',
            'permissions' => ['view_dashboard']
        ],
        'POS' => [
            'enabled' => true,
            'route_prefix' => 'pos',
            'permissions' => ['access_pos']
        ],
        'Inventory' => [
            'enabled' => true,
            'route_prefix' => 'inventory',
            'permissions' => ['manage_inventory']
        ],
        'Menu' => [
            'enabled' => true,
            'route_prefix' => 'menu',
            'permissions' => ['manage_menu']
        ],
        'Customers' => [
            'enabled' => true,
            'route_prefix' => 'customers',
            'permissions' => ['manage_customers']
        ],
        'Staff' => [
            'enabled' => true,
            'route_prefix' => 'staff',
            'permissions' => ['manage_staff']
        ],
        'Suppliers' => [
            'enabled' => true,
            'route_prefix' => 'suppliers',
            'permissions' => ['manage_suppliers']
        ],
        'Reports' => [
            'enabled' => true,
            'route_prefix' => 'reports',
            'permissions' => ['view_reports']
        ],
        'Subscriptions' => [
            'enabled' => true,
            'route_prefix' => 'subscription',
            'permissions' => ['manage_subscriptions']
        ],
        'Notifications' => [
            'enabled' => true,
            'route_prefix' => 'notifications',
            'permissions' => ['manage_notifications']
        ],
        'Accounting' => [
            'enabled' => true,
            'route_prefix' => 'accounting',
            'permissions' => ['manage_accounting']
        ],
        'KitchenDisplay' => [
            'enabled' => true,
            'route_prefix' => 'kitchen',
            'permissions' => ['access_kitchen']
        ],
        'API' => [
            'enabled' => true,
            'route_prefix' => 'api/v1',
            'permissions' => ['api_access']
        ]
    ],

    'features' => [
        'multi_restaurant' => true,
        'multi_branch' => true,
        'subscription_billing' => true,
        'real_time_kitchen' => true,
        'loyalty_program' => true,
        'advanced_reporting' => true,
        'accounting_system' => true,
        'notification_system' => true,
        'api_access' => true
    ],

    'subscription' => [
        'grace_days' => env('SUBSCRIPTION_GRACE_DAYS', 7),
        'trial_days' => 14,
        'commission_rate' => 2.9
    ],

    'cache' => [
        'settings_ttl' => 3600,
        'reports_ttl' => 1800,
        'api_ttl' => 300
    ],

    'limits' => [
        'max_restaurants' => 1000,
        'max_branches_per_restaurant' => 50,
        'max_users_per_branch' => 100,
        'max_products_per_restaurant' => 10000,
        'api_rate_limit' => 1000
    ]
];