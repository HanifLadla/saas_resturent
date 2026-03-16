<?php

namespace Modules\Reports\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function generateSalesReport($restaurantId, $startDate, $endDate, $groupBy = 'day')
    {
        $dateFormat = match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m'
        };

        return Order::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('payment_status', 'paid')
            ->selectRaw("
                DATE_FORMAT(created_at, '{$dateFormat}') as period,
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                AVG(total_amount) as avg_order_value
            ")
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    public function getTopProducts($restaurantId, $startDate, $endDate, $limit = 10)
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
            ->take($limit)
            ->get();
    }
}