<?php

namespace Modules\Customers\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Customer;

class CustomerModel extends Model
{
    public static function getCustomerSegments($restaurantId)
    {
        return [
            'vip' => Customer::where('restaurant_id', $restaurantId)->where('total_spent', '>=', 1000)->count(),
            'regular' => Customer::where('restaurant_id', $restaurantId)->where('visit_count', '>=', 5)->count(),
            'new' => Customer::where('restaurant_id', $restaurantId)->where('visit_count', '<=', 1)->count(),
        ];
    }
    
    public static function getLoyaltyStats($restaurantId)
    {
        return Customer::where('restaurant_id', $restaurantId)
            ->selectRaw('AVG(loyalty_points) as avg_points, SUM(loyalty_points) as total_points')
            ->first();
    }
}