<?php

namespace Modules\Subscriptions\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;

class SubscriptionService
{
    public function upgradeSubscription($restaurantId, $planId)
    {
        $plan = SubscriptionPlan::findOrFail($planId);
        $currentSubscription = Subscription::where('restaurant_id', $restaurantId)
            ->where('status', 'active')
            ->first();

        if ($currentSubscription) {
            $currentSubscription->update(['status' => 'cancelled']);
        }

        return Subscription::create([
            'restaurant_id' => $restaurantId,
            'subscription_plan_id' => $planId,
            'starts_at' => now(),
            'expires_at' => $this->calculateExpiryDate($plan->billing_cycle),
            'status' => 'active',
            'amount_paid' => $plan->price,
            'features_snapshot' => $plan->features
        ]);
    }

    private function calculateExpiryDate($billingCycle)
    {
        return match($billingCycle) {
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            'lifetime' => null,
            default => now()->addMonth()
        };
    }
}