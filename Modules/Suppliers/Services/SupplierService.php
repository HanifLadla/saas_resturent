<?php

namespace Modules\Suppliers\Services;

use App\Models\Supplier;

class SupplierService
{
    public function createSupplier($data)
    {
        return Supplier::create([
            'restaurant_id' => auth()->user()->restaurant->id,
            ...$data
        ]);
    }

    public function getPerformanceMetrics($supplierId)
    {
        $supplier = Supplier::findOrFail($supplierId);
        
        return [
            'total_orders' => $supplier->purchaseOrders()->count(),
            'total_amount' => $supplier->purchaseOrders()->sum('total_amount'),
            'avg_delivery_time' => 5,
            'on_time_delivery_rate' => 95
        ];
    }
}