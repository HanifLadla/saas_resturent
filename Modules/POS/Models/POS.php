<?php

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Order;
use App\Models\Product;

class POS extends Model
{
    public static function getActiveOrders($branchId)
    {
        return Order::where('branch_id', $branchId)
            ->whereIn('status', ['pending', 'confirmed', 'preparing'])
            ->with(['items.product', 'customer'])
            ->latest()
            ->get();
    }
    
    public static function getAvailableProducts($restaurantId)
    {
        return Product::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->with('category')
            ->get();
    }
}