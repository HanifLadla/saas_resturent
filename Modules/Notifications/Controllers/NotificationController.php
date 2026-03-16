<?php

namespace Modules\Notifications\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class NotificationController extends Controller
{
    public function index()
    {
        $restaurant = auth()->user()->restaurant;
        
        $stats = [
            'total_sent' => DB::table('notifications')->where('restaurant_id', $restaurant->id)->count(),
            'sent_today' => DB::table('notifications')->where('restaurant_id', $restaurant->id)
                ->whereDate('created_at', today())->count(),
            'email_enabled' => $restaurant->getSetting('notifications', 'email_enabled', true),
            'sms_enabled' => $restaurant->getSetting('notifications', 'sms_enabled', false),
            'whatsapp_enabled' => $restaurant->getSetting('notifications', 'whatsapp_enabled', false)
        ];

        return response()->json($stats);
    }

    public function templates(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:email,sms,whatsapp',
                'event' => 'required|string',
                'subject' => 'required_if:type,email|string|max:255',
                'content' => 'required|string',
                'variables' => 'nullable|array',
                'is_active' => 'boolean'
            ]);

            DB::table('notification_templates')->insert([
                'restaurant_id' => $restaurant->id,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'event' => $validated['event'],
                'subject' => $validated['subject'] ?? null,
                'content' => $validated['content'],
                'variables' => json_encode($validated['variables'] ?? []),
                'is_active' => $validated['is_active'] ?? true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json(['success' => true]);
        }

        $templates = DB::table('notification_templates')
            ->where('restaurant_id', $restaurant->id)
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->event, fn($q) => $q->where('event', $request->event))
            ->get();

        return response()->json($templates);
    }

    public function sendCustomNotification(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:email,sms,whatsapp',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'string',
            'subject' => 'required_if:type,email|string|max:255',
            'message' => 'required|string',
            'schedule_at' => 'nullable|date|after:now'
        ]);

        $restaurant = auth()->user()->restaurant;

        foreach ($validated['recipients'] as $recipient) {
            $notificationData = [
                'restaurant_id' => $restaurant->id,
                'type' => $validated['type'],
                'recipient' => $recipient,
                'subject' => $validated['subject'] ?? null,
                'message' => $validated['message'],
                'status' => 'pending',
                'scheduled_at' => $validated['schedule_at'] ?? now(),
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ];

            if ($validated['schedule_at'] ?? null) {
                // Schedule for later
                DB::table('notifications')->insert($notificationData);
            } else {
                // Send immediately
                $this->sendNotification($notificationData);
            }
        }

        return response()->json(['success' => true, 'message' => 'Notifications queued successfully']);
    }

    public function sendOrderNotification(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'type' => 'required|in:order_confirmed,order_ready,order_delivered',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,sms,whatsapp'
        ]);

        $order = Order::with(['customer', 'restaurant'])->findOrFail($validated['order_id']);
        
        if (!$order->customer) {
            return response()->json(['success' => false, 'message' => 'Order has no customer'], 422);
        }

        foreach ($validated['channels'] as $channel) {
            $template = $this->getTemplate($order->restaurant_id, $channel, $validated['type']);
            
            if ($template) {
                $message = $this->processTemplate($template, $order);
                
                $this->sendNotification([
                    'restaurant_id' => $order->restaurant_id,
                    'type' => $channel,
                    'recipient' => $this->getRecipientAddress($order->customer, $channel),
                    'subject' => $template->subject ?? null,
                    'message' => $message,
                    'status' => 'pending',
                    'order_id' => $order->id,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    public function sendBulkNotification(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:email,sms,whatsapp',
            'segment' => 'required|in:all,vip,regular,new,birthday',
            'subject' => 'required_if:type,email|string|max:255',
            'message' => 'required|string',
            'schedule_at' => 'nullable|date|after:now'
        ]);

        $restaurant = auth()->user()->restaurant;
        $customers = $this->getCustomersBySegment($restaurant->id, $validated['segment']);

        $batchId = uniqid('batch_');
        $notifications = [];

        foreach ($customers as $customer) {
            $recipient = $this->getRecipientAddress($customer, $validated['type']);
            
            if ($recipient) {
                $notifications[] = [
                    'restaurant_id' => $restaurant->id,
                    'type' => $validated['type'],
                    'recipient' => $recipient,
                    'subject' => $validated['subject'] ?? null,
                    'message' => $this->personalizeMessage($validated['message'], $customer),
                    'status' => 'pending',
                    'batch_id' => $batchId,
                    'customer_id' => $customer->id,
                    'scheduled_at' => $validated['schedule_at'] ?? now(),
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }

        DB::table('notifications')->insert($notifications);

        return response()->json([
            'success' => true, 
            'message' => count($notifications) . ' notifications queued',
            'batch_id' => $batchId
        ]);
    }

    public function history(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        
        $notifications = DB::table('notifications')
            ->where('restaurant_id', $restaurant->id)
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->batch_id, fn($q) => $q->where('batch_id', $request->batch_id))
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($notifications);
    }

    public function settings(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        $settingsService = app('App\Services\GlobalSettingsService');

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'email_enabled' => 'boolean',
                'sms_enabled' => 'boolean',
                'whatsapp_enabled' => 'boolean',
                'email_provider' => 'nullable|string',
                'sms_provider' => 'nullable|string',
                'whatsapp_provider' => 'nullable|string',
                'email_settings' => 'nullable|array',
                'sms_settings' => 'nullable|array',
                'whatsapp_settings' => 'nullable|array'
            ]);

            foreach ($validated as $key => $value) {
                $settingsService->set($restaurant->id, 'notifications', $key, $value, 
                    is_array($value) ? 'json' : (is_bool($value) ? 'boolean' : 'string'));
            }

            return response()->json(['success' => true]);
        }

        $settings = $settingsService->getCategory($restaurant->id, 'notifications');
        return response()->json($settings);
    }

    public function testNotification(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:email,sms,whatsapp',
            'recipient' => 'required|string',
            'message' => 'required|string'
        ]);

        $result = $this->sendNotification([
            'restaurant_id' => auth()->user()->restaurant->id,
            'type' => $validated['type'],
            'recipient' => $validated['recipient'],
            'subject' => 'Test Notification',
            'message' => $validated['message'],
            'status' => 'pending',
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message']
        ]);
    }

    private function sendNotification(array $notificationData)
    {
        try {
            switch ($notificationData['type']) {
                case 'email':
                    return $this->sendEmail($notificationData);
                case 'sms':
                    return $this->sendSMS($notificationData);
                case 'whatsapp':
                    return $this->sendWhatsApp($notificationData);
            }
        } catch (\Exception $e) {
            DB::table('notifications')->insert(array_merge($notificationData, [
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]));

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function sendEmail(array $data)
    {
        Mail::raw($data['message'], function ($message) use ($data) {
            $message->to($data['recipient'])
                   ->subject($data['subject'] ?? 'Notification');
        });

        DB::table('notifications')->insert(array_merge($data, [
            'status' => 'sent',
            'sent_at' => now()
        ]));

        return ['success' => true, 'message' => 'Email sent successfully'];
    }

    private function sendSMS(array $data)
    {
        // Integration with SMS provider (Twilio, etc.)
        // This is a placeholder implementation
        
        DB::table('notifications')->insert(array_merge($data, [
            'status' => 'sent',
            'sent_at' => now()
        ]));

        return ['success' => true, 'message' => 'SMS sent successfully'];
    }

    private function sendWhatsApp(array $data)
    {
        // Integration with WhatsApp Business API
        // This is a placeholder implementation
        
        DB::table('notifications')->insert(array_merge($data, [
            'status' => 'sent',
            'sent_at' => now()
        ]));

        return ['success' => true, 'message' => 'WhatsApp message sent successfully'];
    }

    private function getTemplate($restaurantId, $type, $event)
    {
        return DB::table('notification_templates')
            ->where('restaurant_id', $restaurantId)
            ->where('type', $type)
            ->where('event', $event)
            ->where('is_active', true)
            ->first();
    }

    private function processTemplate($template, $order)
    {
        $variables = [
            '{customer_name}' => $order->customer->name,
            '{order_number}' => $order->order_number,
            '{restaurant_name}' => $order->restaurant->name,
            '{total_amount}' => '$' . number_format($order->total_amount, 2),
            '{order_type}' => ucfirst(str_replace('_', ' ', $order->type))
        ];

        return str_replace(array_keys($variables), array_values($variables), $template->content);
    }

    private function getRecipientAddress($customer, $type)
    {
        return match($type) {
            'email' => $customer->email,
            'sms', 'whatsapp' => $customer->phone,
            default => null
        };
    }

    private function getCustomersBySegment($restaurantId, $segment)
    {
        $query = Customer::where('restaurant_id', $restaurantId);

        return match($segment) {
            'vip' => $query->where('total_spent', '>=', 1000)->get(),
            'regular' => $query->where('visit_count', '>=', 5)->get(),
            'new' => $query->where('visit_count', '<=', 1)->get(),
            'birthday' => $query->whereRaw('MONTH(date_of_birth) = ? AND DAY(date_of_birth) = ?', 
                [now()->month, now()->day])->get(),
            default => $query->get()
        };
    }

    private function personalizeMessage($message, $customer)
    {
        $variables = [
            '{customer_name}' => $customer->name,
            '{loyalty_points}' => $customer->loyalty_points
        ];

        return str_replace(array_keys($variables), array_values($variables), $message);
    }
}