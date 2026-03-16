<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'address', 'logo',
        'currency', 'timezone', 'settings', 'status', 'subscription_expires_at'
    ];

    protected $casts = [
        'settings' => 'array',
        'subscription_expires_at' => 'datetime'
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }

    public function globalSettings(): HasMany
    {
        return $this->hasMany(GlobalSetting::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && 
               ($this->subscription_expires_at === null || $this->subscription_expires_at->isFuture());
    }

    public function hasFeature(string $feature): bool
    {
        return $this->subscription?->features_snapshot[$feature] ?? false;
    }

    public function getSetting(string $category, string $key, $default = null)
    {
        $setting = $this->globalSettings()
            ->where('category', $category)
            ->where('key', $key)
            ->first();

        if (!$setting) return $default;

        return match($setting->type) {
            'json' => json_decode($setting->value, true),
            'boolean' => (bool) $setting->value,
            'integer' => (int) $setting->value,
            default => $setting->value
        };
    }
}