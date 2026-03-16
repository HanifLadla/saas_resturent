<?php

namespace Modules\Inventory\Services;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function adjustStock($itemId, $type, $quantity, $reason)
    {
        return DB::transaction(function () use ($itemId, $type, $quantity, $reason) {
            $item = InventoryItem::findOrFail($itemId);
            $oldStock = $item->current_stock;
            
            $newStock = match($type) {
                'add' => $oldStock + $quantity,
                'subtract' => max(0, $oldStock - $quantity),
                'set' => $quantity
            };
            
            $item->update(['current_stock' => $newStock]);
            
            DB::table('stock_adjustments')->insert([
                'inventory_item_id' => $itemId,
                'old_quantity' => $oldStock,
                'new_quantity' => $newStock,
                'adjustment' => $newStock - $oldStock,
                'reason' => $reason,
                'user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            return $item;
        });
    }

    public function createPurchaseOrder($data)
    {
        return DB::transaction(function () use ($data) {
            $subtotal = collect($data['items'])->sum(fn($item) => 
                $item['quantity_ordered'] * $item['unit_price']
            );

            $po = PurchaseOrder::create([
                'restaurant_id' => auth()->user()->restaurant->id,
                'supplier_id' => $data['supplier_id'],
                'po_number' => $this->generatePONumber(),
                'status' => 'draft',
                'total_amount' => $subtotal
            ]);

            foreach ($data['items'] as $item) {
                DB::table('purchase_order_items')->insert([
                    'purchase_order_id' => $po->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity_ordered'] * $item['unit_price'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return $po;
        });
    }

    private function generatePONumber()
    {
        $date = now()->format('Ymd');
        $sequence = PurchaseOrder::whereDate('created_at', today())->count() + 1;
        return 'PO-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}