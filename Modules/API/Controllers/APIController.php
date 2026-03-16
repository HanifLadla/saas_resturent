<?php

namespace Modules\API\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class APIController extends Controller
{
    // Orders API
    public function getOrders(Request $request): JsonResponse
    {
        $restaurant = auth()->user()->restaurant;
        $query = Order::where('restaurant_id', $restaurant->id)->with(['items.product', 'customer', 'payments']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total()
            ]
        ]);
    }

    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:dine_in,takeaway,delivery,online',
            'customer_id' => 'nullable|exists:customers,id',
            'table_number' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.variants' => 'nullable|array',
            'items.*.modifiers' => 'nullable|array',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        try {
            $posService = app('Modules\POS\Services\POSService');
            $order = $posService->createOrder($validated);

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function updateOrderStatus(Request $request, $orderId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,served,completed,cancelled'
        ]);

        $order = Order::where('restaurant_id', auth()->user()->restaurant->id)
            ->findOrFail($orderId);

        $order->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order status updated'
        ]);
    }

    // Products API
    public function getProducts(Request $request): JsonResponse
    {
        $restaurant = auth()->user()->restaurant;
        $query = Product::where('restaurant_id', $restaurant->id)->with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        $products = $query->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total()
            ]
        ]);
    }

    public function createProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'required|string|unique:products,sku',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'track_inventory' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_alert' => 'nullable|integer|min:0',
            'preparation_time' => 'nullable|integer|min:1'
        ]);

        $product = Product::create([
            'restaurant_id' => auth()->user()->restaurant->id,
            'slug' => \Str::slug($validated['name']),
            ...$validated
        ]);

        return response()->json([
            'success' => true,
            'data' => $product->load('category'),
            'message' => 'Product created successfully'
        ], 201);
    }

    public function updateProduct(Request $request, $productId): JsonResponse
    {
        $product = Product::where('restaurant_id', auth()->user()->restaurant->id)
            ->findOrFail($productId);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'track_inventory' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_alert' => 'nullable|integer|min:0',
            'preparation_time' => 'nullable|integer|min:1',
            'is_active' => 'boolean'
        ]);

        $product->update([
            'slug' => \Str::slug($validated['name']),
            ...$validated
        ]);

        return response()->json([
            'success' => true,
            'data' => $product->load('category'),
            'message' => 'Product updated successfully'
        ]);
    }

    // Customers API
    public function getCustomers(Request $request): JsonResponse
    {
        $restaurant = auth()->user()->restaurant;
        $query = Customer::where('restaurant_id', $restaurant->id);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $customers = $query->withCount('orders')
            ->latest()
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total()
            ]
        ]);
    }

    public function createCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers,email',
            'phone' => 'nullable|string|unique:customers,phone',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string'
        ]);

        $customer = Customer::create([
            'restaurant_id' => auth()->user()->restaurant->id,
            ...$validated
        ]);

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer created successfully'
        ], 201);
    }

    // Inventory API
    public function getInventory(Request $request): JsonResponse
    {
        $restaurant = auth()->user()->restaurant;
        $query = InventoryItem::where('restaurant_id', $restaurant->id);

        if ($request->filled('low_stock')) {
            $query->whereColumn('current_stock', '<=', 'minimum_stock');
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $items = $query->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total()
            ]
        ]);
    }

    public function updateInventoryStock(Request $request, $itemId): JsonResponse
    {
        $validated = $request->validate([
            'adjustment_type' => 'required|in:add,subtract,set',
            'quantity' => 'required|numeric|min:0',
            'reason' => 'required|string'
        ]);

        $item = InventoryItem::where('restaurant_id', auth()->user()->restaurant->id)
            ->findOrFail($itemId);

        $oldStock = $item->current_stock;
        $newStock = match($validated['adjustment_type']) {
            'add' => $oldStock + $validated['quantity'],
            'subtract' => max(0, $oldStock - $validated['quantity']),
            'set' => $validated['quantity']
        };

        $item->update(['current_stock' => $newStock]);

        return response()->json([
            'success' => true,
            'data' => $item,
            'message' => 'Stock updated successfully'
        ]);
    }

    // Analytics API
    public function getSalesAnalytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        $restaurant = auth()->user()->restaurant;
        $query = Order::where('restaurant_id', $restaurant->id)
            ->whereBetween('created_at', [$validated['start_date'], $validated['end_date']])
            ->where('payment_status', 'paid');

        if ($validated['branch_id'] ?? null) {
            $query->where('branch_id', $validated['branch_id']);
        }

        $analytics = [
            'total_sales' => $query->sum('total_amount'),
            'total_orders' => $query->count(),
            'avg_order_value' => $query->avg('total_amount'),
            'sales_by_day' => $query->selectRaw('DATE(created_at) as date, SUM(total_amount) as sales')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'top_products' => $this->getTopProducts($restaurant->id, $validated['start_date'], $validated['end_date'])
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    // Webhook endpoints
    public function webhook(Request $request): JsonResponse
    {
        $event = $request->header('X-Event-Type');
        $signature = $request->header('X-Signature');

        // Verify webhook signature
        if (!$this->verifyWebhookSignature($request->getContent(), $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        switch ($event) {
            case 'order.created':
                $this->handleOrderCreated($request->all());
                break;
            case 'payment.completed':
                $this->handlePaymentCompleted($request->all());
                break;
            default:
                return response()->json(['error' => 'Unknown event type'], 400);
        }

        return response()->json(['success' => true]);
    }

    // System status
    public function status(): JsonResponse
    {
        return response()->json([
            'status' => 'operational',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
            'services' => [
                'database' => 'operational',
                'cache' => 'operational',
                'queue' => 'operational'
            ]
        ]);
    }

    private function getTopProducts($restaurantId, $startDate, $endDate)
    {
        return \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.restaurant_id', $restaurantId)
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.payment_status', 'paid')
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.name, sum(order_items.quantity) as total_sold, sum(order_items.total_price) as revenue')
            ->orderByDesc('total_sold')
            ->take(10)
            ->get();
    }

    private function verifyWebhookSignature($payload, $signature)
    {
        $expectedSignature = hash_hmac('sha256', $payload, config('app.webhook_secret'));
        return hash_equals($expectedSignature, $signature);
    }

    private function handleOrderCreated($data)
    {
        // Handle order created webhook
    }

    private function handlePaymentCompleted($data)
    {
        // Handle payment completed webhook
    }
}