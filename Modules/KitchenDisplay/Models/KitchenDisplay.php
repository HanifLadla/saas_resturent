<?php

namespace Modules\KitchenDisplay\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\KitchenOrder;
use App\Models\Order;

class KitchenDisplay extends Model
{
    public static function sendOrderToKitchen(Order $order)
    {
        $stations = $order->branch->kitchenStations()->where('is_active', true)->get();
        
        foreach ($stations as $station) {
            $stationItems = self::getItemsForStation($order, $station);
            
            if (!empty($stationItems)) {
                KitchenOrder::create([
                    'order_id' => $order->id,
                    'kitchen_station_id' => $station->id,
                    'items' => $stationItems,
                    'status' => 'pending',
                    'estimated_time' => self::calculateEstimatedTime($stationItems)
                ]);
            }
        }
    }
    
    private static function getItemsForStation(Order $order, $station)
    {
        $assignedCategories = $station->assigned_categories ?? [];
        $stationItems = [];
        
        foreach ($order->items as $item) {
            if (in_array($item->product->category_id, $assignedCategories)) {
                $stationItems[] = [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'variants' => $item->variants,
                    'modifiers' => $item->modifiers,
                    'special_instructions' => $item->special_instructions
                ];
            }
        }
        
        return $stationItems;
    }
    
    private static function calculateEstimatedTime(array $items): int
    {
        return collect($items)->max('preparation_time') ?? 15;
    }
}