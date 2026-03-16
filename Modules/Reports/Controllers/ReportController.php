<?php

namespace Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index()
    {
        $reportTypes = [
            'sales' => [
                'title' => 'Sales Reports',
                'description' => 'Revenue, orders, and sales performance',
                'reports' => ['daily_sales', 'monthly_sales', 'product_sales', 'payment_methods']
            ],
            'inventory' => [
                'title' => 'Inventory Reports',
                'description' => 'Stock levels, movements, and valuations',
                'reports' => ['stock_levels', 'low_stock', 'stock_movements', 'inventory_valuation']
            ],
            'customer' => [
                'title' => 'Customer Reports',
                'description' => 'Customer analytics and behavior',
                'reports' => ['customer_analysis', 'loyalty_program', 'customer_segments']
            ],
            'financial' => [
                'title' => 'Financial Reports',
                'description' => 'Accounting and financial statements',
                'reports' => ['profit_loss', 'cash_flow', 'expense_analysis']
            ]
        ];

        return response()->json($reportTypes);
    }

    public function salesReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
            'group_by' => 'nullable|in:day,week,month'
        ]);

        $restaurant = auth()->user()->restaurant;
        $query = Order::where('restaurant_id', $restaurant->id)
            ->whereBetween('created_at', [$validated['start_date'], $validated['end_date']])
            ->where('payment_status', 'paid');

        if ($validated['branch_id'] ?? null) {
            $query->where('branch_id', $validated['branch_id']);
        }

        $groupBy = $validated['group_by'] ?? 'day';
        $dateFormat = match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m'
        };

        $salesData = $query->selectRaw("
            DATE_FORMAT(created_at, '{$dateFormat}') as period,
            COUNT(*) as total_orders,
            SUM(total_amount) as total_sales,
            AVG(total_amount) as avg_order_value,
            SUM(tax_amount) as total_tax,
            SUM(discount_amount) as total_discounts
        ")
        ->groupBy('period')
        ->orderBy('period')
        ->get();

        $summary = [
            'total_orders' => $salesData->sum('total_orders'),
            'total_sales' => $salesData->sum('total_sales'),
            'avg_order_value' => $salesData->avg('avg_order_value'),
            'total_tax' => $salesData->sum('total_tax'),
            'total_discounts' => $salesData->sum('total_discounts')
        ];

        return response()->json([
            'data' => $salesData,
            'summary' => $summary,
            'period' => $groupBy
        ]);
    }

    public function productSalesReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'category_id' => 'nullable|exists:categories,id',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        $restaurant = auth()->user()->restaurant;
        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.restaurant_id', $restaurant->id)
            ->whereBetween('orders.created_at', [$validated['start_date'], $validated['end_date']])
            ->where('orders.payment_status', 'paid');

        if ($validated['category_id'] ?? null) {
            $query->where('products.category_id', $validated['category_id']);
        }

        $productSales = $query->select([
            'products.name as product_name',
            'categories.name as category_name',
            'products.price as unit_price',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.total_price) as total_revenue'),
            DB::raw('COUNT(DISTINCT orders.id) as order_count')
        ])
        ->groupBy('products.id', 'products.name', 'categories.name', 'products.price')
        ->orderByDesc('total_revenue')
        ->limit($validated['limit'] ?? 50)
        ->get();

        return response()->json($productSales);
    }

    public function inventoryReport(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        $reportType = $request->get('type', 'stock_levels');

        switch ($reportType) {
            case 'stock_levels':
                $data = InventoryItem::where('restaurant_id', $restaurant->id)
                    ->select([
                        'name', 'sku', 'unit', 'current_stock', 'minimum_stock', 'maximum_stock',
                        'unit_cost', DB::raw('current_stock * unit_cost as stock_value')
                    ])
                    ->get();
                break;

            case 'low_stock':
                $data = InventoryItem::where('restaurant_id', $restaurant->id)
                    ->whereColumn('current_stock', '<=', 'minimum_stock')
                    ->select(['name', 'sku', 'current_stock', 'minimum_stock', 'unit'])
                    ->get();
                break;

            case 'inventory_valuation':
                $data = InventoryItem::where('restaurant_id', $restaurant->id)
                    ->selectRaw('
                        SUM(current_stock * unit_cost) as total_value,
                        COUNT(*) as total_items,
                        SUM(CASE WHEN current_stock <= minimum_stock THEN 1 ELSE 0 END) as low_stock_items
                    ')
                    ->first();
                break;

            default:
                $data = [];
        }

        return response()->json(['type' => $reportType, 'data' => $data]);
    }

    public function customerReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'segment' => 'nullable|in:all,vip,regular,new'
        ]);

        $restaurant = auth()->user()->restaurant;
        $query = Customer::where('restaurant_id', $restaurant->id);

        if ($validated['segment'] ?? null) {
            match($validated['segment']) {
                'vip' => $query->where('total_spent', '>=', 1000),
                'regular' => $query->where('visit_count', '>=', 5),
                'new' => $query->where('visit_count', '<=', 1)
            };
        }

        $customers = $query->with(['orders' => function($q) use ($validated) {
            $q->whereBetween('created_at', [$validated['start_date'], $validated['end_date']]);
        }])
        ->get()
        ->map(function($customer) {
            return [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'total_spent' => $customer->total_spent,
                'visit_count' => $customer->visit_count,
                'loyalty_points' => $customer->loyalty_points,
                'last_visit' => $customer->last_visit_at,
                'orders_in_period' => $customer->orders->count(),
                'spent_in_period' => $customer->orders->sum('total_amount')
            ];
        });

        return response()->json($customers);
    }

    public function financialReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:profit_loss,cash_flow,expense_analysis'
        ]);

        $restaurant = auth()->user()->restaurant;
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        switch ($validated['type']) {
            case 'profit_loss':
                $revenue = Order::where('restaurant_id', $restaurant->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->where('payment_status', 'paid')
                    ->sum('total_amount');

                $cogs = DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->where('orders.restaurant_id', $restaurant->id)
                    ->whereBetween('orders.created_at', [$startDate, $endDate])
                    ->where('orders.payment_status', 'paid')
                    ->sum(DB::raw('order_items.quantity * products.cost_price'));

                $expenses = DB::table('expenses')
                    ->where('restaurant_id', $restaurant->id)
                    ->whereBetween('expense_date', [$startDate, $endDate])
                    ->sum('amount');

                $data = [
                    'revenue' => $revenue,
                    'cost_of_goods_sold' => $cogs,
                    'gross_profit' => $revenue - $cogs,
                    'operating_expenses' => $expenses,
                    'net_profit' => $revenue - $cogs - $expenses,
                    'profit_margin' => $revenue > 0 ? (($revenue - $cogs - $expenses) / $revenue) * 100 : 0
                ];
                break;

            case 'cash_flow':
                $data = $this->getCashFlowData($restaurant->id, $startDate, $endDate);
                break;

            case 'expense_analysis':
                $data = $this->getExpenseAnalysis($restaurant->id, $startDate, $endDate);
                break;
        }

        return response()->json(['type' => $validated['type'], 'data' => $data]);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|string',
            'format' => 'required|in:pdf,excel,csv',
            'data' => 'required|array'
        ]);

        $filename = $validated['report_type'] . '_' . now()->format('Y-m-d_H-i-s');

        switch ($validated['format']) {
            case 'pdf':
                $pdf = Pdf::loadView('reports.pdf', [
                    'title' => ucwords(str_replace('_', ' ', $validated['report_type'])),
                    'data' => $validated['data'],
                    'generated_at' => now()
                ]);
                return $pdf->download($filename . '.pdf');

            case 'excel':
                return Excel::download(
                    new ReportExport($validated['data']), 
                    $filename . '.xlsx'
                );

            case 'csv':
                return Excel::download(
                    new ReportExport($validated['data']), 
                    $filename . '.csv'
                );
        }
    }

    public function scheduledReports(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'report_type' => 'required|string',
                'frequency' => 'required|in:daily,weekly,monthly',
                'format' => 'required|in:pdf,excel',
                'email_recipients' => 'required|array',
                'email_recipients.*' => 'email',
                'parameters' => 'nullable|array'
            ]);

            // Store scheduled report configuration
            DB::table('scheduled_reports')->insert([
                'restaurant_id' => auth()->user()->restaurant->id,
                'name' => $validated['name'],
                'report_type' => $validated['report_type'],
                'frequency' => $validated['frequency'],
                'format' => $validated['format'],
                'email_recipients' => json_encode($validated['email_recipients']),
                'parameters' => json_encode($validated['parameters'] ?? []),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json(['success' => true]);
        }

        $scheduledReports = DB::table('scheduled_reports')
            ->where('restaurant_id', auth()->user()->restaurant->id)
            ->get();

        return response()->json($scheduledReports);
    }

    private function getCashFlowData($restaurantId, $startDate, $endDate)
    {
        $cashInflows = Order::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('payment_status', 'paid')
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as amount')
            ->groupBy('date')
            ->get();

        $cashOutflows = DB::table('expenses')
            ->where('restaurant_id', $restaurantId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->selectRaw('DATE(expense_date) as date, SUM(amount) as amount')
            ->groupBy('date')
            ->get();

        return [
            'inflows' => $cashInflows,
            'outflows' => $cashOutflows,
            'net_cash_flow' => $cashInflows->sum('amount') - $cashOutflows->sum('amount')
        ];
    }

    private function getExpenseAnalysis($restaurantId, $startDate, $endDate)
    {
        return DB::table('expenses')
            ->where('restaurant_id', $restaurantId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->selectRaw('category, SUM(amount) as total_amount, COUNT(*) as transaction_count')
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->get();
    }
}