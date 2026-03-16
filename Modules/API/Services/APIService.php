<?php

namespace Modules\API\Services;

class APIService
{
    public function validateApiKey($key)
    {
        return \DB::table('api_keys')
            ->where('key', $key)
            ->where('is_active', true)
            ->exists();
    }

    public function logRequest($endpoint, $method, $responseCode)
    {
        \DB::table('api_logs')->insert([
            'endpoint' => $endpoint,
            'method' => $method,
            'response_code' => $responseCode,
            'ip_address' => request()->ip(),
            'created_at' => now()
        ]);
    }

    public function generateApiKey($restaurantId, $name, $permissions = [])
    {
        $key = 'qb_' . bin2hex(random_bytes(32));
        
        \DB::table('api_keys')->insert([
            'restaurant_id' => $restaurantId,
            'name' => $name,
            'key' => $key,
            'permissions' => json_encode($permissions),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return $key;
    }
}