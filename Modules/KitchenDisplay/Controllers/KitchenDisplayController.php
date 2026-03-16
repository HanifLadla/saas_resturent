<?php

namespace Modules\KitchenDisplay\Controllers;

use App\Http\Controllers\Controller;
use App\Models\KitchenOrder;
use App\Models\KitchenStation;
use Illuminate\Http\Request;

class KitchenDisplayController extends Controller
{
    public function display(Request $request)
    {
        $branch = auth()->user()->branch;
        $stationId = $request->get('station_id');
        
        $stations = KitchenStation::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->get();
            
        return view('kitchen-display.index', compact('stations', 'stationId'));
    }

    public function getDisplayData(Request $request)
    {
        $branch = auth()->user()->branch;
        $stationId = $request->get('station_id');
        
        $query = KitchenOrder::with(['order.items.product', 'kitchenStation'])
            ->whereHas('order', fn($q) => $q->where('branch_id', $branch->id))
            ->whereIn('status', ['pending', 'preparing'])
            ->orderBy('created_at');
        
        if ($stationId) {
            $query->where('kitchen_station_id', $stationId);
        }
        
        $orders = $query->get()->map(function ($kitchenOrder) {
            return [
                'id' => $kitchenOrder->id,
                'order_number' => $kitchenOrder->order->order_number,
                'order_type' => $kitchenOrder->order->type,
                'table_number' => $kitchenOrder->order->table_number,
                'status' => $kitchenOrder->status,
                'items' => $kitchenOrder->items,
                'estimated_time' => $kitchenOrder->estimated_time,
                'elapsed_time' => $kitchenOrder->created_at->diffInMinutes(now()),
                'priority' => $this->calculatePriority($kitchenOrder),
                'station' => $kitchenOrder->kitchenStation->name
            ];
        });

        return response()->json([
            'orders' => $orders,
            'statistics' => [
                'pending_count' => $orders->where('status', 'pending')->count(),
                'preparing_count' => $orders->where('status', 'preparing')->count()
            ]
        ]);
    }

    public function updateStatus(Request $request, KitchenOrder $kitchenOrder)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,preparing,ready'
        ]);

        $kitchenOrder->update([
            'status' => $validated['status'],
            'started_at' => $validated['status'] === 'preparing' ? now() : $kitchenOrder->started_at,
            'completed_at' => $validated['status'] === 'ready' ? now() : null
        ]);

        // Check if all kitchen orders for this order are ready
        $allReady = KitchenOrder::where('order_id', $kitchenOrder->order_id)
            ->where('status', '!=', 'ready')
            ->doesntExist();

        if ($allReady) {
            $kitchenOrder->order->update(['status' => 'ready']);
        }

        return response()->json(['success' => true, 'kitchen_order' => $kitchenOrder]);
    }

    private function calculatePriority(KitchenOrder $kitchenOrder): string
    {
        $elapsedMinutes = $kitchenOrder->created_at->diffInMinutes(now());
        $estimatedTime = $kitchenOrder->estimated_time;
        
        if ($elapsedMinutes > $estimatedTime * 1.5) return 'urgent';
        if ($elapsedMinutes > $estimatedTime) return 'high';
        if ($elapsedMinutes > $estimatedTime * 0.7) return 'medium';
        
        return 'normal';
    }
}