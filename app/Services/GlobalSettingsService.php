<?php

namespace App\Services;

use App\Models\GlobalSetting;
use Illuminate\Support\Facades\Cache;

class GlobalSettingsService
{
    private const CACHE_PREFIX = 'settings:';
    private const CACHE_TTL = 3600;

    public function get(int $restaurantId, string $category, string $key, $default = null)
    {
        $cacheKey = $this->getCacheKey($restaurantId, $category, $key);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($restaurantId, $category, $key, $default) {
            $setting = GlobalSetting::where('restaurant_id', $restaurantId)
                ->where('category', $category)
                ->where('key', $key)
                ->first();

            if (!$setting) return $default;

            return $this->castValue($setting->value, $setting->type);
        });
    }

    public function set(int $restaurantId, string $category, string $key, $value, string $type = 'string'): void
    {
        $processedValue = $this->processValue($value, $type);
        
        GlobalSetting::updateOrCreate(
            ['restaurant_id' => $restaurantId, 'category' => $category, 'key' => $key],
            ['value' => $processedValue, 'type' => $type]
        );

        Cache::forget($this->getCacheKey($restaurantId, $category, $key));
    }

    public function getCategory(int $restaurantId, string $category): array
    {
        $settings = GlobalSetting::where('restaurant_id', $restaurantId)
            ->where('category', $category)
            ->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $this->castValue($setting->value, $setting->type);
        }

        return $result;
    }

    public function getDefaultSettings(): array
    {
        return [
            'pos' => [
                'auto_print_receipt' => ['value' => true, 'type' => 'boolean'],
                'allow_discount' => ['value' => true, 'type' => 'boolean'],
                'max_discount_percent' => ['value' => 20, 'type' => 'integer']
            ],
            'tax' => [
                'rates' => [
                    'value' => [['name' => 'VAT', 'rate' => 10.0, 'is_active' => true]],
                    'type' => 'json'
                ]
            ],
            'kitchen_display' => [
                'auto_refresh_seconds' => ['value' => 30, 'type' => 'integer'],
                'sound_alerts' => ['value' => true, 'type' => 'boolean']
            ]
        ];
    }

    private function getCacheKey(int $restaurantId, string $category, string $key): string
    {
        return self::CACHE_PREFIX . $restaurantId . ':' . $category . ':' . $key;
    }

    private function castValue($value, string $type)
    {
        return match($type) {
            'json' => json_decode($value, true),
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            default => $value
        };
    }

    private function processValue($value, string $type): string
    {
        return match($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value
        };
    }
}