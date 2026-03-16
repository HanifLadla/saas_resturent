<?php

namespace Modules\Subscriptions\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;

class SubscriptionModel extends Model
{
    public static function getActiveSubscription($restaurantId)
    {
        return Subscription::where('restaurant_id', $restaurantId)
            ->where('status', 'active')
            ->with('subscriptionPlan')
            ->first();
    }
    
    public static function getAvailablePlans()
    {
        return SubscriptionPlan::where('is_active', true)
            ->orderBy('price')
            ->get();
    }
}