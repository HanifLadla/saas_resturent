<?php

namespace Modules\Suppliers\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        $query = Supplier::where('restaurant_id', $restaurant->id);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('contact_person', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $suppliers = $query->withCount(['purchaseOrders as total_orders'])
            ->withSum(['purchaseOrders as total_amount' => function($q) {
                $q->where('status', '!=', 'cancelled');
            }], 'total_amount')
            ->paginate(20);

        $stats = [
            'total_suppliers' => Supplier::where('restaurant_id', $restaurant->id)->count(),
            'active_suppliers' => Supplier::where('restaurant_id', $restaurant->id)->where('is_active', true)->count(),
            'total_spent' => PurchaseOrder::where('restaurant_id', $restaurant->id)
                ->where('status', '!=', 'cancelled')->sum('total_amount'),
            'pending_orders' => PurchaseOrder::where('restaurant_id', $restaurant->id)
                ->where('status', 'pending')->count()
        ];

        return response()->json(['suppliers' => $suppliers, 'stats' => $stats]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'required|string',
            'address' => 'required|string',
            'contact_person' => 'nullable|string|max:255',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|integer|min:0'
        ]);

        $supplier = Supplier::create([
            'restaurant_id' => auth()->user()->restaurant->id,
            ...$validated
        ]);

        return response()->json(['success' => true, 'supplier' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'required|string',
            'address' => 'required|string',
            'contact_person' => 'nullable|string|max:255',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);

        $supplier->update($validated);
        return response()->json(['success' => true, 'supplier' => $supplier]);
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['purchaseOrders' => function($query) {
            $query->latest()->take(10);
        }]);

        $stats = [
            'total_orders' => $supplier->purchaseOrders()->count(),
            'total_amount' => $supplier->purchaseOrders()->where('status', '!=', 'cancelled')->sum('total_amount'),
            'pending_orders' => $supplier->purchaseOrders()->where('status', 'pending')->count(),
            'avg_order_value' => $supplier->purchaseOrders()->where('status', '!=', 'cancelled')->avg('total_amount') ?? 0,
            'last_order_date' => $supplier->purchaseOrders()->latest()->first()?->created_at,
            'payment_history' => $this->getPaymentHistory($supplier)
        ];

        return response()->json(['supplier' => $supplier, 'stats' => $stats]);
    }

    public function purchaseOrders(Supplier $supplier, Request $request)
    {
        $orders = $supplier->purchaseOrders()
            ->with(['items.inventoryItem'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    public function performance()
    {
        $restaurant = auth()->user()->restaurant;
        
        $performance = Supplier::where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->withCount(['purchaseOrders as total_orders'])
            ->withSum(['purchaseOrders as total_amount' => function($q) {
                $q->where('status', '!=', 'cancelled');
            }], 'total_amount')
            ->withAvg(['purchaseOrders as avg_delivery_time' => function($q) {
                $q->whereNotNull('expected_delivery_date')
                  ->where('status', 'received');
            }], DB::raw('DATEDIFF(updated_at, expected_delivery_date)'))
            ->get()
            ->map(function($supplier) {
                $onTimeDeliveries = $supplier->purchaseOrders()
                    ->where('status', 'received')
                    ->whereNotNull('expected_delivery_date')
                    ->whereRaw('updated_at <= expected_delivery_date')
                    ->count();
                
                $totalDeliveries = $supplier->purchaseOrders()
                    ->where('status', 'received')
                    ->whereNotNull('expected_delivery_date')
                    ->count();

                $supplier->on_time_delivery_rate = $totalDeliveries > 0 
                    ? ($onTimeDeliveries / $totalDeliveries) * 100 
                    : 0;

                return $supplier;
            });

        return response()->json($performance);
    }

    public function paymentTerms(Supplier $supplier)
    {
        $outstandingAmount = $supplier->purchaseOrders()
            ->where('status', 'received')
            ->whereDoesntHave('payments', function($q) {
                $q->where('status', 'completed');
            })
            ->sum('total_amount');

        $overdueOrders = $supplier->purchaseOrders()
            ->where('status', 'received')
            ->where('created_at', '<', now()->subDays($supplier->payment_terms))
            ->whereDoesntHave('payments', function($q) {
                $q->where('status', 'completed');
            })
            ->count();

        return response()->json([
            'payment_terms' => $supplier->payment_terms,
            'credit_limit' => $supplier->credit_limit,
            'outstanding_amount' => $outstandingAmount,
            'available_credit' => max(0, $supplier->credit_limit - $outstandingAmount),
            'overdue_orders' => $overdueOrders
        ]);
    }

    public function contactHistory(Supplier $supplier)
    {
        // This would typically come from a communications log table
        $history = [
            [
                'type' => 'email',
                'subject' => 'Purchase Order #PO-20241201-0001',
                'date' => now()->subDays(2),
                'status' => 'sent'
            ],
            [
                'type' => 'phone',
                'subject' => 'Delivery inquiry',
                'date' => now()->subDays(5),
                'notes' => 'Confirmed delivery for tomorrow'
            ]
        ];

        return response()->json($history);
    }

    public function catalog(Supplier $supplier)
    {
        // Supplier's product catalog - would be stored in supplier_products table
        $catalog = DB::table('supplier_products')
            ->where('supplier_id', $supplier->id)
            ->join('inventory_items', 'supplier_products.inventory_item_id', '=', 'inventory_items.id')
            ->select([
                'inventory_items.name',
                'inventory_items.sku',
                'supplier_products.supplier_price',
                'supplier_products.minimum_order_quantity',
                'supplier_products.lead_time_days',
                'supplier_products.is_available'
            ])
            ->get();

        return response()->json($catalog);
    }

    public function updateCatalog(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.supplier_price' => 'required|numeric|min:0',
            'items.*.minimum_order_quantity' => 'required|integer|min:1',
            'items.*.lead_time_days' => 'required|integer|min:0',
            'items.*.is_available' => 'boolean'
        ]);

        foreach ($validated['items'] as $item) {
            DB::table('supplier_products')->updateOrInsert(
                [
                    'supplier_id' => $supplier->id,
                    'inventory_item_id' => $item['inventory_item_id']
                ],
                [
                    'supplier_price' => $item['supplier_price'],
                    'minimum_order_quantity' => $item['minimum_order_quantity'],
                    'lead_time_days' => $item['lead_time_days'],
                    'is_available' => $item['is_available'] ?? true,
                    'updated_at' => now()
                ]
            );
        }

        return response()->json(['success' => true]);
    }

    private function getPaymentHistory(Supplier $supplier)
    {
        return DB::table('purchase_order_payments')
            ->join('purchase_orders', 'purchase_order_payments.purchase_order_id', '=', 'purchase_orders.id')
            ->where('purchase_orders.supplier_id', $supplier->id)
            ->select([
                'purchase_order_payments.amount',
                'purchase_order_payments.payment_date',
                'purchase_order_payments.method',
                'purchase_orders.po_number'
            ])
            ->latest('payment_date')
            ->take(10)
            ->get();
    }
}