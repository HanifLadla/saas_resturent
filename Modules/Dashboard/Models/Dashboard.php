<?php

namespace Modules\Dashboard\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;

class Dashboard extends Model
{
    public static function getSalesStats($restaurantId, $branchId = null)
    {
        $query = Order::where('restaurant_id', $restaurantId)
            ->where('payment_status', 'paid')
            ->whereDate('created_at', today());
            
        if ($branchId) $query->where('branch_id', $branchId);
        
        return [
            'today_sales' => $query->sum('total_amount'),
            'today_orders' => $query->count(),
            'avg_order_value' => $query->avg('total_amount') ?? 0
        ];
    }
    
    public static function getInventoryAlerts($restaurantId)
    {
        return Product::where('restaurant_id', $restaurantId)
            ->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
            ->count();
    }
}