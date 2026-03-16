<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;

class Inventory extends Model
{
    public static function getLowStockItems($restaurantId)
    {
        return InventoryItem::where('restaurant_id', $restaurantId)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->get();
    }
    
    public static function getPendingOrders($restaurantId)
    {
        return PurchaseOrder::where('restaurant_id', $restaurantId)
            ->where('status', 'pending')
            ->with('supplier')
            ->get();
    }
    
    public static function getStockValue($restaurantId)
    {
        return InventoryItem::where('restaurant_id', $restaurantId)
            ->selectRaw('SUM(current_stock * unit_cost) as total_value')
            ->first()
            ->total_value ?? 0;
    }
}