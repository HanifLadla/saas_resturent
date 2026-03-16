<?php

namespace Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        $restaurant = auth()->user()->restaurant;
        
        $stats = [
            'total_items' => InventoryItem::where('restaurant_id', $restaurant->id)->count(),
            'low_stock_items' => InventoryItem::where('restaurant_id', $restaurant->id)
                ->whereColumn('current_stock', '<=', 'minimum_stock')->count(),
            'out_of_stock' => InventoryItem::where('restaurant_id', $restaurant->id)
                ->where('current_stock', '<=', 0)->count(),
            'pending_orders' => PurchaseOrder::where('restaurant_id', $restaurant->id)
                ->where('status', 'pending')->count()
        ];

        return view('inventory.index', compact('stats'));
    }

    public function items(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        $query = InventoryItem::where('restaurant_id', $restaurant->id);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            match($request->status) {
                'low_stock' => $query->whereColumn('current_stock', '<=', 'minimum_stock'),
                'out_of_stock' => $query->where('current_stock', '<=', 0),
                'in_stock' => $query->where('current_stock', '>', 0)
            };
        }

        $items = $query->paginate(20);
        return response()->json($items);
    }

    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:inventory_items,sku',
            'description' => 'nullable|string',
            'unit' => 'required|string',
            'current_stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'maximum_stock' => 'required|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0'
        ]);

        $item = InventoryItem::create([
            'restaurant_id' => auth()->user()->restaurant->id,
            ...$validated
        ]);

        return response()->json(['success' => true, 'item' => $item]);
    }

    public function updateItem(Request $request, InventoryItem $item)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string',
            'minimum_stock' => 'required|numeric|min:0',
            'maximum_stock' => 'required|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0'
        ]);

        $item->update($validated);
        return response()->json(['success' => true, 'item' => $item]);
    }

    public function adjustStock(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'adjustment_type' => 'required|in:add,subtract,set',
            'quantity' => 'required|numeric|min:0',
            'reason' => 'required|string'
        ]);

        $item = InventoryItem::findOrFail($validated['item_id']);
        $oldStock = $item->current_stock;

        $newStock = match($validated['adjustment_type']) {
            'add' => $oldStock + $validated['quantity'],
            'subtract' => max(0, $oldStock - $validated['quantity']),
            'set' => $validated['quantity']
        };

        $item->update(['current_stock' => $newStock]);

        // Log adjustment
        DB::table('stock_adjustments')->insert([
            'inventory_item_id' => $item->id,
            'old_quantity' => $oldStock,
            'new_quantity' => $newStock,
            'adjustment' => $newStock - $oldStock,
            'reason' => $validated['reason'],
            'user_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['success' => true, 'item' => $item->fresh()]);
    }

    public function purchaseOrders(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        $orders = PurchaseOrder::where('restaurant_id', $restaurant->id)
            ->with(['supplier', 'items.inventoryItem'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    public function createPurchaseOrder(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'expected_delivery_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0'
        ]);

        return DB::transaction(function () use ($validated) {
            $restaurant = auth()->user()->restaurant;
            $branch = auth()->user()->branch;

            $subtotal = collect($validated['items'])->sum(fn($item) => 
                $item['quantity_ordered'] * $item['unit_price']
            );

            $po = PurchaseOrder::create([
                'restaurant_id' => $restaurant->id,
                'branch_id' => $branch->id,
                'supplier_id' => $validated['supplier_id'],
                'po_number' => $this->generatePONumber(),
                'order_date' => now()->toDateString(),
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'status' => 'draft',
                'subtotal' => $subtotal,
                'total_amount' => $subtotal
            ]);

            foreach ($validated['items'] as $item) {
                $po->items()->create($item);
            }

            return response()->json(['success' => true, 'purchase_order' => $po->load('items')]);
        });
    }

    public function receivePurchaseOrder(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received' => 'required|numeric|min:0'
        ]);

        return DB::transaction(function () use ($validated, $purchaseOrder) {
            foreach ($validated['items'] as $itemData) {
                $poItem = $purchaseOrder->items()->findOrFail($itemData['id']);
                $poItem->update(['quantity_received' => $itemData['quantity_received']]);

                // Update inventory stock
                $inventoryItem = $poItem->inventoryItem;
                $inventoryItem->increment('current_stock', $itemData['quantity_received']);
            }

            $purchaseOrder->update(['status' => 'received']);

            return response()->json(['success' => true, 'purchase_order' => $purchaseOrder->fresh()]);
        });
    }

    public function lowStockAlerts()
    {
        $restaurant = auth()->user()->restaurant;
        $alerts = InventoryItem::where('restaurant_id', $restaurant->id)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->get();

        return response()->json($alerts);
    }

    private function generatePONumber(): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');
        $sequence = PurchaseOrder::whereDate('created_at', today())->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}