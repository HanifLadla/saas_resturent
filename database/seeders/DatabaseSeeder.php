<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Subscription Plans
        DB::table('subscription_plans')->insert([
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small restaurants',
                'price' => 29.99,
                'billing_cycle' => 'monthly',
                'features' => json_encode(['pos', 'basic_reports', 'customer_management']),
                'max_branches' => 1,
                'max_users' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For growing restaurant chains',
                'price' => 79.99,
                'billing_cycle' => 'monthly',
                'features' => json_encode(['pos', 'inventory', 'accounting', 'reports', 'kitchen_display']),
                'max_branches' => 5,
                'max_users' => 25,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited everything',
                'price' => 199.99,
                'billing_cycle' => 'monthly',
                'features' => json_encode(['all_features']),
                'max_branches' => 999,
                'max_users' => 999,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // Sample Restaurant
        $restaurantId = DB::table('restaurants')->insertGetId([
            'name' => 'Demo Restaurant',
            'slug' => 'demo-restaurant',
            'email' => 'demo@restaurant.com',
            'phone' => '+1234567890',
            'address' => '123 Main Street, City, State',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
            'subscription_expires_at' => now()->addYear(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Sample Branch
        $branchId = DB::table('branches')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'phone' => '+1234567890',
            'address' => '123 Main Street, City, State',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Sample Users
        DB::table('users')->insert([
            [
                'restaurant_id' => $restaurantId,
                'branch_id' => $branchId,
                'name' => 'Restaurant Admin',
                'email' => 'admin@demo.com',
                'password' => Hash::make('password'),
                'role' => 'restaurant_admin',
                'permissions' => json_encode(['manage_staff', 'manage_settings', 'view_reports']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'restaurant_id' => $restaurantId,
                'branch_id' => $branchId,
                'name' => 'Cashier',
                'email' => 'cashier@demo.com',
                'password' => Hash::make('password'),
                'role' => 'cashier',
                'permissions' => json_encode(['access_pos', 'manage_customers']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // Sample Categories
        $categoryId = DB::table('categories')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Main Dishes',
            'slug' => 'main-dishes',
            'description' => 'Our signature main dishes',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Sample Products
        DB::table('products')->insert([
            [
                'restaurant_id' => $restaurantId,
                'category_id' => $categoryId,
                'name' => 'Grilled Chicken',
                'slug' => 'grilled-chicken',
                'description' => 'Delicious grilled chicken with herbs',
                'sku' => 'GC001',
                'price' => 15.99,
                'cost_price' => 8.00,
                'track_inventory' => true,
                'stock_quantity' => 50,
                'low_stock_alert' => 10,
                'preparation_time' => 20,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'restaurant_id' => $restaurantId,
                'category_id' => $categoryId,
                'name' => 'Beef Burger',
                'slug' => 'beef-burger',
                'description' => 'Juicy beef burger with fries',
                'sku' => 'BB001',
                'price' => 12.99,
                'cost_price' => 6.50,
                'track_inventory' => true,
                'stock_quantity' => 30,
                'low_stock_alert' => 5,
                'preparation_time' => 15,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // Sample Customers
        DB::table('customers')->insert([
            [
                'restaurant_id' => $restaurantId,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567891',
                'loyalty_points' => 150,
                'total_spent' => 250.00,
                'visit_count' => 8,
                'last_visit_at' => now()->subDays(2),
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // Sample Kitchen Station
        DB::table('kitchen_stations')->insert([
            [
                'restaurant_id' => $restaurantId,
                'branch_id' => $branchId,
                'name' => 'Grill Station',
                'code' => 'GRILL',
                'description' => 'Main grilling station',
                'assigned_categories' => json_encode([$categoryId]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // Sample Global Settings
        $settings = [
            ['pos', 'auto_print_receipt', 'true', 'boolean'],
            ['pos', 'allow_discount', 'true', 'boolean'],
            ['tax', 'default_rate', '10', 'integer'],
            ['kitchen_display', 'auto_refresh_seconds', '30', 'integer']
        ];

        foreach ($settings as $setting) {
            DB::table('global_settings')->insert([
                'restaurant_id' => $restaurantId,
                'category' => $setting[0],
                'key' => $setting[1],
                'value' => $setting[2],
                'type' => $setting[3],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Sample Chart of Accounts
        $accounts = [
            ['1001', 'Cash', 'asset', 'current_asset', 1000.00],
            ['1002', 'Bank Account', 'asset', 'current_asset', 5000.00],
            ['4001', 'Sales Revenue', 'revenue', 'sales_revenue', 0.00],
            ['5001', 'Cost of Goods Sold', 'expense', 'cost_of_goods_sold', 0.00],
            ['6001', 'Operating Expenses', 'expense', 'operating_expense', 0.00]
        ];

        foreach ($accounts as $account) {
            DB::table('chart_of_accounts')->insert([
                'restaurant_id' => $restaurantId,
                'account_code' => $account[0],
                'account_name' => $account[1],
                'account_type' => $account[2],
                'account_subtype' => $account[3],
                'opening_balance' => $account[4],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}