<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = ['restaurant_id', 'name', 'code', 'phone', 'address', 'settings', 'is_active'];
    protected $casts = ['settings' => 'array', 'is_active' => 'boolean'];

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
}

class Category extends Model
{
    protected $fillable = ['restaurant_id', 'name', 'slug', 'description', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
}

class Customer extends Model
{
    protected $fillable = ['restaurant_id', 'name', 'email', 'phone', 'loyalty_points', 'total_spent', 'visit_count'];
    protected $casts = ['total_spent' => 'decimal:2'];

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
}

class GlobalSetting extends Model
{
    protected $fillable = ['restaurant_id', 'category', 'key', 'value', 'type'];

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
}

class InventoryItem extends Model
{
    protected $fillable = ['restaurant_id', 'name', 'sku', 'current_stock', 'minimum_stock', 'unit_cost'];
    protected $casts = ['current_stock' => 'decimal:3', 'unit_cost' => 'decimal:2'];

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
}

class KitchenOrder extends Model
{
    protected $fillable = ['order_id', 'kitchen_station_id', 'items', 'status', 'estimated_time'];
    protected $casts = ['items' => 'array'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function kitchenStation(): BelongsTo { return $this->belongsTo(KitchenStation::class); }
}

class KitchenStation extends Model
{
    protected $fillable = ['restaurant_id', 'branch_id', 'name', 'code', 'assigned_categories'];
    protected $casts = ['assigned_categories' => 'array'];

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
}

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_id', 'product_name', 'quantity', 'unit_price', 'total_price'];
    protected $casts = ['unit_price' => 'decimal:2', 'total_price' => 'decimal:2'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}

class Payment extends Model
{
    protected $fillable = ['order_id', 'method', 'amount', 'status'];
    protected $casts = ['amount' => 'decimal:2'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}

class PurchaseOrder extends Model
{
    protected $fillable = ['restaurant_id', 'supplier_id', 'po_number', 'status', 'total_amount'];
    protected $casts = ['total_amount' => 'decimal:2'];

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
}

class Subscription extends Model
{
    protected $fillable = ['restaurant_id', 'subscription_plan_id', 'starts_at', 'expires_at', 'status', 'amount_paid'];
    protected $casts = ['starts_at' => 'datetime', 'expires_at' => 'datetime', 'amount_paid' => 'decimal:2'];

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
    public function subscriptionPlan(): BelongsTo { return $this->belongsTo(SubscriptionPlan::class); }
}

class SubscriptionPlan extends Model
{
    protected $fillable = ['name', 'price', 'billing_cycle', 'features', 'max_branches', 'max_users'];
    protected $casts = ['features' => 'array', 'price' => 'decimal:2'];

    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }
}

class Supplier extends Model
{
    protected $fillable = ['restaurant_id', 'name', 'email', 'phone', 'address', 'credit_limit'];
    protected $casts = ['credit_limit' => 'decimal:2'];

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
    public function purchaseOrders(): HasMany { return $this->hasMany(PurchaseOrder::class); }
}

class User extends \Illuminate\Foundation\Auth\User
{
    protected $fillable = ['restaurant_id', 'branch_id', 'name', 'email', 'password', 'role', 'permissions'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['permissions' => 'array'];

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
}