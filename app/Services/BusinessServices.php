<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function createCustomer(array $data): Customer
    {
        return Customer::create([
            'restaurant_id' => auth()->user()->restaurant->id,
            ...$data
        ]);
    }

    public function addLoyaltyPoints(Customer $customer, int $points, string $reason): void
    {
        $customer->increment('loyalty_points', $points);
        
        DB::table('loyalty_transactions')->insert([
            'customer_id' => $customer->id,
            'points' => $points,
            'type' => 'earned',
            'reason' => $reason,
            'user_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function updateCustomerStats(Customer $customer, Order $order): void
    {
        $customer->increment('total_spent', $order->total_amount);
        $customer->increment('visit_count');
        $customer->update(['last_visit_at' => now()]);
    }
}

class InventoryService
{
    public function adjustStock(int $itemId, string $type, float $quantity, string $reason): void
    {
        $item = \App\Models\InventoryItem::findOrFail($itemId);
        $oldStock = $item->current_stock;
        
        $newStock = match($type) {
            'add' => $oldStock + $quantity,
            'subtract' => max(0, $oldStock - $quantity),
            'set' => $quantity
        };
        
        $item->update(['current_stock' => $newStock]);
        
        DB::table('stock_adjustments')->insert([
            'inventory_item_id' => $itemId,
            'old_quantity' => $oldStock,
            'new_quantity' => $newStock,
            'adjustment' => $newStock - $oldStock,
            'reason' => $reason,
            'user_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function getLowStockAlerts(int $restaurantId): array
    {
        return \App\Models\InventoryItem::where('restaurant_id', $restaurantId)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->get()
            ->toArray();
    }
}

class ReportService
{
    public function generateSalesReport(int $restaurantId, string $startDate, string $endDate): array
    {
        return Order::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('payment_status', 'paid')
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as orders,
                SUM(total_amount) as sales,
                AVG(total_amount) as avg_order
            ')
            ->groupBy('date')
            ->get()
            ->toArray();
    }

    public function exportToPDF(array $data, string $template): string
    {
        // PDF generation logic
        return 'report_' . time() . '.pdf';
    }

    public function exportToExcel(array $data): string
    {
        // Excel generation logic
        return 'report_' . time() . '.xlsx';
    }
}

class NotificationService
{
    public function sendNotification(string $type, string $recipient, string $message): bool
    {
        try {
            match($type) {
                'email' => $this->sendEmail($recipient, $message),
                'sms' => $this->sendSMS($recipient, $message),
                'whatsapp' => $this->sendWhatsApp($recipient, $message)
            };

            DB::table('notifications')->insert([
                'restaurant_id' => auth()->user()->restaurant->id,
                'type' => $type,
                'recipient' => $recipient,
                'message' => $message,
                'status' => 'sent',
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function sendEmail(string $recipient, string $message): void
    {
        // Email sending logic
    }

    private function sendSMS(string $recipient, string $message): void
    {
        // SMS sending logic
    }

    private function sendWhatsApp(string $recipient, string $message): void
    {
        // WhatsApp sending logic
    }
}