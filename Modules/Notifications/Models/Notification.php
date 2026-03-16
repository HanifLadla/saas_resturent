<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Notification extends Model
{
    public static function sendNotification($restaurantId, $type, $recipient, $message)
    {
        return DB::table('notifications')->insert([
            'restaurant_id' => $restaurantId,
            'type' => $type,
            'recipient' => $recipient,
            'message' => $message,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    
    public static function getNotificationHistory($restaurantId)
    {
        return DB::table('notifications')
            ->where('restaurant_id', $restaurantId)
            ->orderByDesc('created_at')
            ->paginate(50);
    }
}