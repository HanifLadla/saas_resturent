<?php

namespace Modules\Dashboard\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;

class DashboardService
{
    public function getKPIs($restaurantId, $branchId = null)
    {
        $today = Carbon::today();
        $query = Order::where('restaurant_id', $restaurantId)->whereDate('created_at', $today);
        
        if ($branchId) $query->where('branch_id', $branchId);
        
        return [
            'today_sales' => $query->where('payment_status', 'paid')->sum('total_amount'),
            'today_orders' => $query->count(),
            'avg_order_value' => $query->where('payment_status', 'paid')->avg('total_amount') ?? 0,
            'total_customers' => Customer::where('restaurant_id', $restaurantId)->count(),
            'low_stock_items' => Product::where('restaurant_id', $restaurantId)
                ->where('track_inventory', true)
                ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
                ->count()
        ];
    }

    public function getRecentOrders($restaurantId, $branchId = null, $limit = 10)
    {
        $query = Order::where('restaurant_id', $restaurantId)
            ->with(['customer', 'user'])
            ->latest();
            
        if ($branchId) $query->where('branch_id', $branchId);
        
        return $query->take($limit)->get();
    }
}