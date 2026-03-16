<?php

namespace Modules\KitchenDisplay\Services;

use App\Models\KitchenOrder;
use App\Models\Order;

class KitchenDisplayService
{
    public function sendOrderToKitchen(Order $order)
    {
        $stations = $order->branch->kitchenStations()->where('is_active', true)->get();
        
        foreach ($stations as $station) {
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
    }

    private function getItemsForStation(Order $order, $station)
    {
        $assignedCategories = $station->assigned_categories ?? [];
        $stationItems = [];
        
        foreach ($order->items as $item) {
            if (in_array($item->product->category_id, $assignedCategories)) {
                $stationItems[] = [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity
                ];
            }
        }
        
        return $stationItems;
    }

    private function calculateEstimatedTime($items)
    {
        return 15;
    }
}