<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'name', 'code', 'phone', 'address', 
        'latitude', 'longitude', 'settings', 'is_active'
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function kitchenStations(): HasMany
    {
        return $this->hasMany(KitchenStation::class);
    }
}

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'name', 'slug', 'description', 
        'image', 'sort_order', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'name', 'email', 'phone', 'date_of_birth',
        'address', 'loyalty_points', 'total_spent', 'visit_count', 'last_visit_at'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'last_visit_at' => 'datetime',
        'total_spent' => 'decimal:2'
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}

class GlobalSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'category', 'key', 'value', 'type'
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'name', 'sku', 'description', 'unit',
        'current_stock', 'minimum_stock', 'maximum_stock', 'unit_cost', 'is_active'
    ];

    protected $casts = [
        'current_stock' => 'decimal:3',
        'minimum_stock' => 'decimal:3',
        'maximum_stock' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}

class KitchenOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'kitchen_station_id', 'items', 'status',
        'started_at', 'completed_at', 'estimated_time'
    ];

    protected $casts = [
        'items' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function kitchenStation(): BelongsTo
    {
        return $this->belongsTo(KitchenStation::class);
    }
}

class KitchenStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'branch_id', 'name', 'code', 'description',
        'assigned_categories', 'display_settings', 'is_active'
    ];

    protected $casts = [
        'assigned_categories' => 'array',
        'display_settings' => 'array',
        'is_active' => 'boolean'
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function kitchenOrders(): HasMany
    {
        return $this->hasMany(KitchenOrder::class);
    }
}

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'unit_price', 'quantity',
        'variants', 'modifiers', 'total_price', 'status', 'special_instructions'
    ];

    protected $casts = [
        'variants' => 'array',
        'modifiers' => 'array',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'method', 'amount', 'reference_number',
        'gateway_response', 'status', 'processed_at'
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'amount' => 'decimal:2',
        'processed_at' => 'datetime'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'branch_id', 'supplier_id', 'po_number',
        'order_date', 'expected_delivery_date', 'status',
        'subtotal', 'tax_amount', 'total_amount', 'notes'
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2'
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id', 'inventory_item_id', 'quantity_ordered',
        'quantity_received', 'unit_price', 'total_price'
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:3',
        'quantity_received' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'subscription_plan_id', 'starts_at', 'expires_at',
        'status', 'amount_paid', 'features_snapshot'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'features_snapshot' => 'array',
        'amount_paid' => 'decimal:2'
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }
}

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'price', 'billing_cycle',
        'commission_rate', 'features', 'max_branches', 'max_users', 'is_active'
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'name', 'email', 'phone', 'address',
        'contact_person', 'credit_limit', 'payment_terms', 'is_active'
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}

class User extends \Illuminate\Foundation\Auth\User
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'branch_id', 'name', 'email', 'password',
        'phone', 'avatar', 'role', 'permissions', 'is_active'
    ];

    protected $hidden = [
        'password', 'remember_token'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'permissions' => 'array',
        'is_active' => 'boolean'
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }
}