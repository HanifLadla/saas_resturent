<?php

namespace Modules\Reports\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class Report extends Model
{
    public static function getSalesReport($restaurantId, $startDate, $endDate)
    {
        return Order::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('payment_status', 'paid')
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                AVG(total_amount) as avg_order_value
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
    
    public static function getTopProducts($restaurantId, $startDate, $endDate)
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.restaurant_id', $restaurantId)
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.payment_status', 'paid')
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.name, SUM(order_items.quantity) as total_sold, SUM(order_items.total_price) as revenue')
            ->orderByDesc('total_sold')
            ->take(10)
            ->get();
    }
}