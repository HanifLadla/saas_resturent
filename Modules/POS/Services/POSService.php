<?php

namespace Modules\POS\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class POSService
{
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $restaurant = auth()->user()->restaurant;
            $branch = auth()->user()->branch;
            
            // Calculate totals
            $subtotal = 0;
            $orderItems = [];
            
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $unitPrice = $product->price;
                
                // Apply variant pricing if applicable
                if (!empty($item['variants'])) {
                    foreach ($item['variants'] as $variant) {
                        $unitPrice += $variant['price_adjustment'] ?? 0;
                    }
                }
                
                // Apply modifier pricing
                if (!empty($item['modifiers'])) {
                    foreach ($item['modifiers'] as $modifier) {
                        $unitPrice += $modifier['price'] ?? 0;
                    }
                }
                
                $totalPrice = $unitPrice * $item['quantity'];
                $subtotal += $totalPrice;
                
                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $unitPrice,
                    'quantity' => $item['quantity'],
                    'variants' => $item['variants'] ?? null,
                    'modifiers' => $item['modifiers'] ?? null,
                    'total_price' => $totalPrice,
                    'special_instructions' => $item['special_instructions'] ?? null,
                    'status' => 'pending'
                ];
            }
            
            // Calculate taxes
            $taxSettings = $restaurant->getSetting('tax', 'rates', []);
            $taxAmount = $this->calculateTax($subtotal, $taxSettings);
            
            // Apply discount
            $discountAmount = $data['discount_amount'] ?? 0;
            $totalAmount = $subtotal + $taxAmount - $discountAmount;
            
            // Create order
            $order = Order::create([
                'restaurant_id' => $restaurant->id,
                'branch_id' => $branch->id,
                'user_id' => auth()->id(),
                'customer_id' => $data['customer_id'] ?? null,
                'order_number' => $this->generateOrderNumber(),
                'type' => $data['type'],
                'status' => 'pending',
                'table_number' => $data['table_number'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'tax_breakdown' => $this->getTaxBreakdown($subtotal, $taxSettings),
                'payment_status' => 'pending',
                'notes' => $data['notes'] ?? null
            ]);
            
            // Create order items
            foreach ($orderItems as $itemData) {
                $order->items()->create($itemData);
            }
            
            // Update inventory if tracking is enabled
            $this->updateInventory($orderItems);
            
            return $order->load(['items.product', 'customer']);
        });
    }
    
    public function processPayment(Order $order, array $payments): array
    {
        return DB::transaction(function () use ($order, $payments) {
            $totalPaid = 0;
            
            foreach ($payments as $paymentData) {
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'method' => $paymentData['method'],
                    'amount' => $paymentData['amount'],
                    'reference_number' => $paymentData['reference_number'] ?? null,
                    'status' => 'completed',
                    'processed_at' => now()
                ]);
                
                $totalPaid += $payment->amount;
            }
            
            // Update order payment status
            if ($totalPaid >= $order->total_amount) {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed'
                ]);
            } elseif ($totalPaid > 0) {
                $order->update(['payment_status' => 'partial']);
            }
            
            return [
                'total_paid' => $totalPaid,
                'change_due' => max(0, $totalPaid - $order->total_amount),
                'receipt_data' => $this->generateReceiptData($order)
            ];
        });
    }
    
    public function generateReceiptData(Order $order): array
    {
        $restaurant = $order->restaurant;
        $branch = $order->branch;
        
        return [
            'restaurant' => [
                'name' => $restaurant->name,
                'address' => $restaurant->address,
                'phone' => $restaurant->phone,
                'logo' => $restaurant->logo
            ],
            'order' => [
                'number' => $order->order_number,
                'date' => $order->created_at->format('Y-m-d H:i:s'),
                'type' => $order->type,
                'table_number' => $order->table_number,
                'cashier' => $order->user->name
            ],
            'items' => $order->items->map(function ($item) {
                return [
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price
                ];
            }),
            'totals' => [
                'subtotal' => $order->subtotal,
                'tax_amount' => $order->tax_amount,
                'discount_amount' => $order->discount_amount,
                'total_amount' => $order->total_amount
            ]
        ];
    }
    
    private function generateOrderNumber(): string
    {
        $prefix = auth()->user()->branch->code ?? 'ORD';
        $date = now()->format('Ymd');
        $sequence = Order::whereDate('created_at', today())->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
    
    private function calculateTax(float $amount, array $taxSettings): float
    {
        $totalTax = 0;
        
        foreach ($taxSettings as $tax) {
            if ($tax['is_active'] ?? false) {
                $taxAmount = ($amount * $tax['rate']) / 100;
                $totalTax += $taxAmount;
            }
        }
        
        return round($totalTax, 2);
    }
    
    private function getTaxBreakdown(float $amount, array $taxSettings): array
    {
        $breakdown = [];
        
        foreach ($taxSettings as $tax) {
            if ($tax['is_active'] ?? false) {
                $taxAmount = ($amount * $tax['rate']) / 100;
                $breakdown[] = [
                    'name' => $tax['name'],
                    'rate' => $tax['rate'],
                    'amount' => round($taxAmount, 2)
                ];
            }
        }
        
        return $breakdown;
    }
    
    private function updateInventory(array $orderItems): void
    {
        foreach ($orderItems as $item) {
            $product = Product::find($item['product_id']);
            
            if ($product && $product->track_inventory) {
                $product->decrement('stock_quantity', $item['quantity']);
            }
        }
    }
}