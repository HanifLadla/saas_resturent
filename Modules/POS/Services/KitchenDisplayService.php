<?php

namespace Modules\POS\Services;

use App\Models\Order;
use App\Models\KitchenStation;
use App\Models\KitchenOrder;
use Illuminate\Support\Facades\DB;

class KitchenDisplayService
{
    public function sendToKitchen(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $restaurant = $order->restaurant;
            $branch = $order->branch;
            
            // Get all kitchen stations for this branch
            $kitchenStations = KitchenStation::where('branch_id', $branch->id)
                ->where('is_active', true)
                ->get();
            
            foreach ($kitchenStations as $station) {
                $stationItems = $this->getItemsForStation($order, $station);
                
                if (!empty($stationItems)) {
                    KitchenOrder::create([
                        'order_id' => $order->id,
                        'kitchen_station_id' => $station->id,
                        'items' => $stationItems,
                        'status' => 'pending',
                        'estimated_time' => $this->calculateEstimatedTime($stationItems)
                    ]);
                }
            }
            
            // Broadcast to kitchen displays
            broadcast(new \App\Events\NewKitchenOrder($order, $branch->id));
        });
    }
    
    public function updateKitchenOrderStatus(KitchenOrder $kitchenOrder, string $status): void
    {
        $kitchenOrder->update([
            'status' => $status,
            'started_at' => $status === 'preparing' ? now() : $kitchenOrder->started_at,
            'completed_at' => $status === 'ready' ? now() : null
        ]);
        
        // Check if all kitchen orders for this order are ready
        $allKitchenOrders = KitchenOrder::where('order_id', $kitchenOrder->order_id)->get();
        $allReady = $allKitchenOrders->every(fn($ko) => $ko->status === 'ready');
        
        if ($allReady) {
            $kitchenOrder->order->update(['status' => 'ready']);
        }
        
        // Broadcast status update
        broadcast(new \App\Events\KitchenOrderStatusUpdated($kitchenOrder));
    }
    
    public function getKitchenDisplay(int $branchId, int $stationId = null): array
    {
        $query = KitchenOrder::with(['order.items.product', 'kitchenStation'])
            ->whereHas('order', fn($q) => $q->where('branch_id', $branchId))
            ->whereIn('status', ['pending', 'preparing'])
            ->orderBy('created_at');
        
        if ($stationId) {
            $query->where('kitchen_station_id', $stationId);
        }
        
        $kitchenOrders = $query->get();
        
        return [
            'orders' => $kitchenOrders->map(function ($kitchenOrder) {
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
                    'station' => $kitchenOrder->kitchenStation->name,
                    'special_instructions' => $kitchenOrder->order->notes
                ];
            }),
            'statistics' => [
                'pending_count' => $kitchenOrders->where('status', 'pending')->count(),
                'preparing_count' => $kitchenOrders->where('status', 'preparing')->count(),
                'average_time' => $this->getAveragePreparationTime($branchId, $stationId)
            ]
        ];
    }
    
    private function getItemsForStation(Order $order, KitchenStation $station): array
    {
        $assignedCategories = $station->assigned_categories ?? [];
        $stationItems = [];
        
        foreach ($order->items as $item) {
            $product = $item->product;
            
            // Check if product category is assigned to this station
            if (in_array($product->category_id, $assignedCategories)) {
                $stationItems[] = [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'variants' => $item->variants,
                    'modifiers' => $item->modifiers,
                    'special_instructions' => $item->special_instructions,
                    'preparation_time' => $product->preparation_time
                ];
            }
        }
        
        return $stationItems;
    }
    
    private function calculateEstimatedTime(array $items): int
    {
        $maxTime = 0;
        
        foreach ($items as $item) {
            $itemTime = ($item['preparation_time'] ?? 15) * $item['quantity'];
            $maxTime = max($maxTime, $itemTime);
        }
        
        return $maxTime;
    }
    
    private function calculatePriority(KitchenOrder $kitchenOrder): string
    {
        $elapsedMinutes = $kitchenOrder->created_at->diffInMinutes(now());
        $estimatedTime = $kitchenOrder->estimated_time;
        
        if ($elapsedMinutes > $estimatedTime * 1.5) {
            return 'urgent';
        } elseif ($elapsedMinutes > $estimatedTime) {
            return 'high';
        } elseif ($elapsedMinutes > $estimatedTime * 0.7) {
            return 'medium';
        }
        
        return 'normal';
    }
    
    private function getAveragePreparationTime(int $branchId, int $stationId = null): float
    {
        $query = KitchenOrder::whereHas('order', fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'ready')
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at');
        
        if ($stationId) {
            $query->where('kitchen_station_id', $stationId);
        }
        
        $completedOrders = $query->get();
        
        if ($completedOrders->isEmpty()) {
            return 0;
        }
        
        $totalTime = $completedOrders->sum(function ($order) {
            return $order->started_at->diffInMinutes($order->completed_at);
        });
        
        return round($totalTime / $completedOrders->count(), 1);
    }
}