<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'branch_id', 'user_id', 'customer_id', 'order_number',
        'type', 'status', 'table_number', 'subtotal', 'tax_amount', 'discount_amount',
        'total_amount', 'tax_breakdown', 'discount_details', 'payment_status',
        'notes', 'estimated_ready_at', 'completed_at'
    ];

    protected $casts = [
        'tax_breakdown' => 'array',
        'discount_details' => 'array',
        'estimated_ready_at' => 'datetime',
        'completed_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function kitchenOrders(): HasMany
    {
        return $this->hasMany(KitchenOrder::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Accessors & Mutators
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'confirmed' => 'blue',
            'preparing' => 'orange',
            'ready' => 'green',
            'served' => 'purple',
            'completed' => 'gray',
            'cancelled' => 'red',
            default => 'gray'
        };
    }

    public function getPaymentStatusColorAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'yellow',
            'partial' => 'orange',
            'paid' => 'green',
            'refunded' => 'red',
            default => 'gray'
        };
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, $this->total_amount - $this->total_paid);
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->total_paid >= $this->total_amount;
    }

    // Business Logic Methods
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) && 
               $this->payment_status !== 'paid';
    }

    public function canBeRefunded(): bool
    {
        return $this->payment_status === 'paid' && 
               in_array($this->status, ['completed', 'cancelled']);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    public function calculateEstimatedReadyTime(): void
    {
        $maxPreparationTime = $this->items()
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->max('products.preparation_time') ?? 15;

        $this->update([
            'estimated_ready_at' => now()->addMinutes($maxPreparationTime)
        ]);
    }
}