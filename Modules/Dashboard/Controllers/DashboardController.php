<?php

namespace Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        $branch = auth()->user()->branch;
        $period = $request->get('period', 'today');
        
        $dateRange = $this->getDateRange($period);
        
        $analytics = [
            'sales' => $this->getSalesAnalytics($restaurant->id, $branch?->id, $dateRange),
            'orders' => $this->getOrdersAnalytics($restaurant->id, $branch?->id, $dateRange),
            'products' => $this->getProductsAnalytics($restaurant->id),
            'customers' => $this->getCustomersAnalytics($restaurant->id, $dateRange),
            'inventory' => $this->getInventoryAlerts($restaurant->id),
            'recent_orders' => $this->getRecentOrders($restaurant->id, $branch?->id),
            'top_products' => $this->getTopProducts($restaurant->id, $dateRange),
            'hourly_sales' => $this->getHourlySales($restaurant->id, $branch?->id, $dateRange)
        ];

        return view('dashboard.index', compact('analytics', 'period'));
    }

    private function getSalesAnalytics($restaurantId, $branchId, $dateRange)
    {
        $query = Order::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', $dateRange)
            ->where('payment_status', 'paid');
            
        if ($branchId) $query->where('branch_id', $branchId);

        $totalSales = $query->sum('total_amount');
        $totalOrders = $query->count();
        $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

        // Previous period comparison
        $prevRange = $this->getPreviousPeriodRange($dateRange);
        $prevQuery = Order::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', $prevRange)
            ->where('payment_status', 'paid');
        if ($branchId) $prevQuery->where('branch_id', $branchId);
        
        $prevSales = $prevQuery->sum('total_amount');
        $salesGrowth = $prevSales > 0 ? (($totalSales - $prevSales) / $prevSales) * 100 : 0;

        return [
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'avg_order_value' => $avgOrderValue,
            'sales_growth' => $salesGrowth
        ];
    }

    private function getOrdersAnalytics($restaurantId, $branchId, $dateRange)
    {
        $query = Order::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', $dateRange);
            
        if ($branchId) $query->where('branch_id', $branchId);

        $statusCounts = $query->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status')
            ->toArray();

        $typeCounts = $query->groupBy('type')
            ->selectRaw('type, count(*) as count')
            ->pluck('count', 'type')
            ->toArray();

        return [
            'by_status' => $statusCounts,
            'by_type' => $typeCounts,
            'pending_orders' => $statusCounts['pending'] ?? 0,
            'completed_orders' => $statusCounts['completed'] ?? 0
        ];
    }

    private function getProductsAnalytics($restaurantId)
    {
        $totalProducts = Product::where('restaurant_id', $restaurantId)->count();
        $activeProducts = Product::where('restaurant_id', $restaurantId)->where('is_active', true)->count();
        $lowStockProducts = Product::where('restaurant_id', $restaurantId)
            ->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
            ->count();

        return [
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'low_stock_products' => $lowStockProducts
        ];
    }

    private function getCustomersAnalytics($restaurantId, $dateRange)
    {
        $totalCustomers = Customer::where('restaurant_id', $restaurantId)->count();
        $newCustomers = Customer::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', $dateRange)
            ->count();

        return [
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomers
        ];
    }

    private function getInventoryAlerts($restaurantId)
    {
        return Product::where('restaurant_id', $restaurantId)
            ->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
            ->select('name', 'stock_quantity', 'low_stock_alert')
            ->take(5)
            ->get();
    }

    private function getRecentOrders($restaurantId, $branchId)
    {
        $query = Order::where('restaurant_id', $restaurantId)
            ->with(['customer', 'user'])
            ->latest();
            
        if ($branchId) $query->where('branch_id', $branchId);

        return $query->take(10)->get();
    }

    private function getTopProducts($restaurantId, $dateRange)
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.restaurant_id', $restaurantId)
            ->whereBetween('orders.created_at', $dateRange)
            ->where('orders.payment_status', 'paid')
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.name, sum(order_items.quantity) as total_sold, sum(order_items.total_price) as revenue')
            ->orderByDesc('total_sold')
            ->take(10)
            ->get();
    }

    private function getHourlySales($restaurantId, $branchId, $dateRange)
    {
        $query = Order::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', $dateRange)
            ->where('payment_status', 'paid');
            
        if ($branchId) $query->where('branch_id', $branchId);

        return $query->selectRaw('HOUR(created_at) as hour, sum(total_amount) as sales')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('sales', 'hour')
            ->toArray();
    }

    private function getDateRange($period)
    {
        return match($period) {
            'today' => [Carbon::today(), Carbon::tomorrow()],
            'yesterday' => [Carbon::yesterday(), Carbon::today()],
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            default => [Carbon::today(), Carbon::tomorrow()]
        };
    }

    private function getPreviousPeriodRange($dateRange)
    {
        $diff = $dateRange[1]->diffInDays($dateRange[0]);
        return [
            $dateRange[0]->copy()->subDays($diff),
            $dateRange[0]
        ];
    }
}