<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'category_id', 'name', 'slug', 'description', 'sku',
        'price', 'cost_price', 'image', 'variants', 'track_inventory',
        'stock_quantity', 'low_stock_alert', 'kitchen_stations',
        'preparation_time', 'is_active'
    ];

    protected $casts = [
        'variants' => 'array',
        'kitchen_stations' => 'array',
        'track_inventory' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2'
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->where('track_inventory', true)
                    ->whereColumn('stock_quantity', '<=', 'low_stock_alert');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('track_inventory', true)
                    ->where('stock_quantity', '<=', 0);
    }

    // Accessors
    public function getStockStatusAttribute(): string
    {
        if (!$this->track_inventory) return 'not_tracked';
        
        if ($this->stock_quantity <= 0) return 'out_of_stock';
        if ($this->stock_quantity <= $this->low_stock_alert) return 'low_stock';
        
        return 'in_stock';
    }

    public function getStockStatusColorAttribute(): string
    {
        return match($this->stock_status) {
            'out_of_stock' => 'red',
            'low_stock' => 'yellow',
            'in_stock' => 'green',
            default => 'gray'
        };
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->cost_price <= 0) return 0;
        
        return (($this->price - $this->cost_price) / $this->cost_price) * 100;
    }

    // Business Logic
    public function isAvailable(): bool
    {
        return $this->is_active && 
               (!$this->track_inventory || $this->stock_quantity > 0);
    }

    public function decrementStock(int $quantity): void
    {
        if ($this->track_inventory) {
            $this->decrement('stock_quantity', $quantity);
        }
    }

    public function incrementStock(int $quantity): void
    {
        if ($this->track_inventory) {
            $this->increment('stock_quantity', $quantity);
        }
    }

    public function adjustStock(int $newQuantity, string $reason = null): void
    {
        if ($this->track_inventory) {
            $oldQuantity = $this->stock_quantity;
            $this->update(['stock_quantity' => $newQuantity]);
            
            // Log stock adjustment
            StockAdjustment::create([
                'product_id' => $this->id,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'adjustment' => $newQuantity - $oldQuantity,
                'reason' => $reason,
                'user_id' => auth()->id()
            ]);
        }
    }

    public function calculateVariantPrice(array $selectedVariants = []): float
    {
        $basePrice = $this->price;
        
        foreach ($selectedVariants as $variant) {
            $basePrice += $variant['price_adjustment'] ?? 0;
        }
        
        return $basePrice;
    }
}