<?php

namespace Modules\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Modules\POS\Services\POSService;
use Modules\POS\Services\KitchenDisplayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class POSController extends Controller
{
    public function __construct(
        private POSService $posService,
        private KitchenDisplayService $kdsService
    ) {}

    public function index(): View
    {
        $restaurant = auth()->user()->restaurant;
        $branch = auth()->user()->branch;
        
        $categories = $restaurant->categories()
            ->with(['products' => fn($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $recentOrders = $restaurant->orders()
            ->where('branch_id', $branch->id)
            ->whereDate('created_at', today())
            ->latest()
            ->take(10)
            ->get();

        return view('pos.index', compact('categories', 'recentOrders'));
    }

    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:dine_in,takeaway,delivery',
            'table_number' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.variants' => 'nullable|array',
            'items.*.modifiers' => 'nullable|array',
            'items.*.special_instructions' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        try {
            $order = $this->posService->createOrder($validated);
            
            // Send to kitchen display
            $this->kdsService->sendToKitchen($order);
            
            return response()->json([
                'success' => true,
                'order' => $order->load(['items.product', 'customer']),
                'message' => 'Order created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function processPayment(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|in:cash,card,digital_wallet,bank_transfer',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.reference_number' => 'nullable|string'
        ]);

        try {
            $result = $this->posService->processPayment($order, $validated['payments']);
            
            return response()->json([
                'success' => true,
                'order' => $order->fresh(['payments']),
                'receipt_data' => $result['receipt_data'],
                'message' => 'Payment processed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function updateOrderStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:confirmed,preparing,ready,served,completed,cancelled'
        ]);

        $order->update(['status' => $validated['status']]);
        
        // Broadcast status update
        broadcast(new \App\Events\OrderStatusUpdated($order));
        
        return response()->json([
            'success' => true,
            'order' => $order,
            'message' => 'Order status updated'
        ]);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $query = $request->get('q');
        $restaurant = auth()->user()->restaurant;
        
        $products = $restaurant->products()
            ->where('is_active', true)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%");
            })
            ->with('category')
            ->take(20)
            ->get();

        return response()->json($products);
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $query = $request->get('q');
        $restaurant = auth()->user()->restaurant;
        
        $customers = $restaurant->customers()
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->take(20)
            ->get();

        return response()->json($customers);
    }

    public function printReceipt(Order $order): JsonResponse
    {
        $receiptData = $this->posService->generateReceiptData($order);
        
        return response()->json([
            'success' => true,
            'receipt_data' => $receiptData
        ]);
    }
}