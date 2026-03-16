<?php

namespace Modules\Customers\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function addLoyaltyPoints(Customer $customer, $points, $reason)
    {
        $customer->increment('loyalty_points', $points);
        
        DB::table('loyalty_transactions')->insert([
            'customer_id' => $customer->id,
            'points' => $points,
            'type' => 'earned',
            'reason' => $reason,
            'user_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function redeemPoints(Customer $customer, $points, $orderId = null)
    {
        if ($customer->loyalty_points < $points) {
            throw new \Exception('Insufficient loyalty points');
        }

        $customer->decrement('loyalty_points', $points);
        
        DB::table('loyalty_transactions')->insert([
            'customer_id' => $customer->id,
            'points' => -$points,
            'type' => 'redeemed',
            'reason' => 'Points redeemed',
            'order_id' => $orderId,
            'user_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}