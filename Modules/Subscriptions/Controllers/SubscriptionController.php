<?php

namespace Modules\Subscriptions\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function index()
    {
        $restaurant = auth()->user()->restaurant;
        $currentSubscription = $restaurant->subscription;
        
        $usage = [
            'branches' => $restaurant->branches()->count(),
            'users' => $restaurant->users()->count(),
            'orders_this_month' => $restaurant->orders()->whereMonth('created_at', now()->month)->count(),
            'storage_used' => $this->calculateStorageUsage($restaurant->id)
        ];

        $billingHistory = Subscription::where('restaurant_id', $restaurant->id)
            ->with('subscriptionPlan')
            ->latest()
            ->take(12)
            ->get();

        return response()->json([
            'current_subscription' => $currentSubscription,
            'usage' => $usage,
            'billing_history' => $billingHistory
        ]);
    }

    public function plans()
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price')
            ->get()
            ->map(function($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'price' => $plan->price,
                    'billing_cycle' => $plan->billing_cycle,
                    'features' => $plan->features,
                    'max_branches' => $plan->max_branches,
                    'max_users' => $plan->max_users,
                    'commission_rate' => $plan->commission_rate,
                    'is_popular' => $plan->slug === 'professional' // Mark popular plan
                ];
            });

        return response()->json($plans);
    }

    public function upgrade(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'nullable|in:monthly,yearly',
            'payment_method' => 'required|string'
        ]);

        $restaurant = auth()->user()->restaurant;
        $newPlan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $currentSubscription = $restaurant->subscription;

        return DB::transaction(function () use ($restaurant, $newPlan, $currentSubscription, $validated) {
            // Calculate prorated amount if upgrading mid-cycle
            $proratedAmount = $this->calculateProratedAmount($currentSubscription, $newPlan);
            
            // Create new subscription
            $newSubscription = Subscription::create([
                'restaurant_id' => $restaurant->id,
                'subscription_plan_id' => $newPlan->id,
                'starts_at' => now(),
                'expires_at' => $this->calculateExpiryDate($newPlan->billing_cycle),
                'status' => 'active',
                'amount_paid' => $proratedAmount,
                'features_snapshot' => $newPlan->features
            ]);

            // Deactivate current subscription
            if ($currentSubscription) {
                $currentSubscription->update(['status' => 'cancelled']);
            }

            // Process payment
            $paymentResult = $this->processSubscriptionPayment(
                $restaurant, 
                $proratedAmount, 
                $validated['payment_method']
            );

            if (!$paymentResult['success']) {
                throw new \Exception('Payment failed: ' . $paymentResult['message']);
            }

            // Update restaurant subscription expiry
            $restaurant->update([
                'subscription_expires_at' => $newSubscription->expires_at
            ]);

            return response()->json([
                'success' => true,
                'subscription' => $newSubscription,
                'message' => 'Subscription upgraded successfully'
            ]);
        });
    }

    public function downgrade(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'effective_date' => 'nullable|date|after:today'
        ]);

        $restaurant = auth()->user()->restaurant;
        $newPlan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $currentSubscription = $restaurant->subscription;

        // Validate downgrade is allowed
        if ($newPlan->max_branches < $restaurant->branches()->count()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot downgrade: You have more branches than allowed in the new plan'
            ], 422);
        }

        if ($newPlan->max_users < $restaurant->users()->count()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot downgrade: You have more users than allowed in the new plan'
            ], 422);
        }

        $effectiveDate = $validated['effective_date'] 
            ? Carbon::parse($validated['effective_date'])
            : $currentSubscription->expires_at;

        // Schedule downgrade
        DB::table('subscription_changes')->insert([
            'restaurant_id' => $restaurant->id,
            'current_plan_id' => $currentSubscription->subscription_plan_id,
            'new_plan_id' => $newPlan->id,
            'change_type' => 'downgrade',
            'effective_date' => $effectiveDate,
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Downgrade scheduled for ' . $effectiveDate->format('Y-m-d')
        ]);
    }

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
            'feedback' => 'nullable|string',
            'effective_date' => 'nullable|date|after:today'
        ]);

        $restaurant = auth()->user()->restaurant;
        $currentSubscription = $restaurant->subscription;

        $effectiveDate = $validated['effective_date'] 
            ? Carbon::parse($validated['effective_date'])
            : $currentSubscription->expires_at;

        // Log cancellation
        DB::table('subscription_cancellations')->insert([
            'restaurant_id' => $restaurant->id,
            'subscription_id' => $currentSubscription->id,
            'reason' => $validated['reason'],
            'feedback' => $validated['feedback'],
            'effective_date' => $effectiveDate,
            'cancelled_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Update subscription status
        $currentSubscription->update([
            'status' => 'cancelled',
            'expires_at' => $effectiveDate
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled. Access will continue until ' . $effectiveDate->format('Y-m-d')
        ]);
    }

    public function billingHistory(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        
        $history = Subscription::where('restaurant_id', $restaurant->id)
            ->with(['subscriptionPlan'])
            ->when($request->year, fn($q) => $q->whereYear('created_at', $request->year))
            ->latest()
            ->paginate(20);

        return response()->json($history);
    }

    public function invoices(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        
        $invoices = DB::table('subscription_invoices')
            ->where('restaurant_id', $restaurant->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($invoices);
    }

    public function downloadInvoice($invoiceId)
    {
        $invoice = DB::table('subscription_invoices')
            ->where('id', $invoiceId)
            ->where('restaurant_id', auth()->user()->restaurant->id)
            ->firstOrFail();

        // Generate PDF invoice
        $pdf = \PDF::loadView('invoices.subscription', compact('invoice'));
        
        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }

    public function usageAnalytics()
    {
        $restaurant = auth()->user()->restaurant;
        $currentPlan = $restaurant->subscription->subscriptionPlan;

        $usage = [
            'branches' => [
                'current' => $restaurant->branches()->count(),
                'limit' => $currentPlan->max_branches,
                'percentage' => ($restaurant->branches()->count() / $currentPlan->max_branches) * 100
            ],
            'users' => [
                'current' => $restaurant->users()->count(),
                'limit' => $currentPlan->max_users,
                'percentage' => ($restaurant->users()->count() / $currentPlan->max_users) * 100
            ],
            'orders_this_month' => $restaurant->orders()->whereMonth('created_at', now()->month)->count(),
            'storage' => [
                'used' => $this->calculateStorageUsage($restaurant->id),
                'limit' => $currentPlan->features['storage_limit_gb'] ?? 10,
                'unit' => 'GB'
            ]
        ];

        return response()->json($usage);
    }

    public function paymentMethods()
    {
        $restaurant = auth()->user()->restaurant;
        
        // This would integrate with payment gateway to get saved payment methods
        $paymentMethods = [
            [
                'id' => 'pm_1234',
                'type' => 'card',
                'last4' => '4242',
                'brand' => 'visa',
                'exp_month' => 12,
                'exp_year' => 2025,
                'is_default' => true
            ]
        ];

        return response()->json($paymentMethods);
    }

    public function addPaymentMethod(Request $request)
    {
        $validated = $request->validate([
            'payment_method_id' => 'required|string',
            'set_as_default' => 'boolean'
        ]);

        // This would integrate with payment gateway to save payment method
        
        return response()->json(['success' => true]);
    }

    private function calculateProratedAmount($currentSubscription, $newPlan)
    {
        if (!$currentSubscription) {
            return $newPlan->price;
        }

        $remainingDays = now()->diffInDays($currentSubscription->expires_at);
        $totalDays = $currentSubscription->starts_at->diffInDays($currentSubscription->expires_at);
        
        $unusedAmount = ($remainingDays / $totalDays) * $currentSubscription->amount_paid;
        
        return max(0, $newPlan->price - $unusedAmount);
    }

    private function calculateExpiryDate($billingCycle)
    {
        return match($billingCycle) {
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            'lifetime' => null,
            default => now()->addMonth()
        };
    }

    private function processSubscriptionPayment($restaurant, $amount, $paymentMethod)
    {
        // This would integrate with actual payment gateway (Stripe, PayPal, etc.)
        return [
            'success' => true,
            'transaction_id' => 'txn_' . uniqid(),
            'amount' => $amount
        ];
    }

    private function calculateStorageUsage($restaurantId)
    {
        // Calculate storage usage in GB
        $imageStorage = DB::table('products')
            ->where('restaurant_id', $restaurantId)
            ->whereNotNull('image')
            ->count() * 0.5; // Assume 0.5MB per image

        $documentStorage = DB::table('documents')
            ->where('restaurant_id', $restaurantId)
            ->sum('file_size') / (1024 * 1024 * 1024); // Convert to GB

        return round($imageStorage + $documentStorage, 2);
    }
}