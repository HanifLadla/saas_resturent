<?php

namespace Modules\API\Models;

use Illuminate\Database\Eloquent\Model;

class API extends Model
{
    public static function validateApiKey($key)
    {
        return \DB::table('api_keys')
            ->where('key', $key)
            ->where('is_active', true)
            ->exists();
    }
    
    public static function logApiRequest($endpoint, $method, $response_code)
    {
        return \DB::table('api_logs')->insert([
            'endpoint' => $endpoint,
            'method' => $method,
            'response_code' => $response_code,
            'created_at' => now()
        ]);
    }
}