<?php

namespace Modules\Suppliers\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Supplier;

class SupplierModel extends Model
{
    public static function getSupplierStats($restaurantId)
    {
        return [
            'total_suppliers' => Supplier::where('restaurant_id', $restaurantId)->count(),
            'active_suppliers' => Supplier::where('restaurant_id', $restaurantId)->where('is_active', true)->count()
        ];
    }
}