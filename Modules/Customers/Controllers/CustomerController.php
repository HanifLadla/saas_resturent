<?php

namespace Modules\Customers\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        $query = Customer::where('restaurant_id', $restaurant->id);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('segment')) {
            match($request->segment) {
                'vip' => $query->where('total_spent', '>=', 1000),
                'regular' => $query->where('visit_count', '>=', 5),
                'new' => $query->where('visit_count', '<=', 1),
                'inactive' => $query->where('last_visit_at', '<', now()->subMonths(3))
            };
        }

        $customers = $query->withCount('orders')
            ->orderBy('total_spent', 'desc')
            ->paginate(20);

        $stats = [
            'total_customers' => Customer::where('restaurant_id', $restaurant->id)->count(),
            'new_this_month' => Customer::where('restaurant_id', $restaurant->id)
                ->whereMonth('created_at', now()->month)->count(),
            'vip_customers' => Customer::where('restaurant_id', $restaurant->id)
                ->where('total_spent', '>=', 1000)->count(),
            'avg_loyalty_points' => Customer::where('restaurant_id', $restaurant->id)
                ->avg('loyalty_points') ?? 0
        ];

        return response()->json(['customers' => $customers, 'stats' => $stats]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers,email',
            'phone' => 'nullable|string|unique:customers,phone',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string'
        ]);

        $customer = Customer::create([
            'restaurant_id' => auth()->user()->restaurant->id,
            ...$validated
        ]);

        return response()->json(['success' => true, 'customer' => $customer]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|unique:customers,phone,' . $customer->id,
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string'
        ]);

        $customer->update($validated);
        return response()->json(['success' => true, 'customer' => $customer]);
    }

    public function show(Customer $customer)
    {
        $customer->load(['orders' => function($query) {
            $query->latest()->take(10);
        }]);

        $stats = [
            'total_orders' => $customer->orders()->count(),
            'total_spent' => $customer->total_spent,
            'avg_order_value' => $customer->orders()->avg('total_amount') ?? 0,
            'last_visit' => $customer->last_visit_at,
            'loyalty_points' => $customer->loyalty_points,
            'favorite_items' => $this->getFavoriteItems($customer)
        ];

        return response()->json(['customer' => $customer, 'stats' => $stats]);
    }

    public function orders(Customer $customer, Request $request)
    {
        $orders = $customer->orders()
            ->with(['items.product', 'payments'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    public function addLoyaltyPoints(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1',
            'reason' => 'required|string'
        ]);

        $customer->increment('loyalty_points', $validated['points']);

        // Log loyalty transaction
        DB::table('loyalty_transactions')->insert([
            'customer_id' => $customer->id,
            'points' => $validated['points'],
            'type' => 'earned',
            'reason' => $validated['reason'],
            'user_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['success' => true, 'customer' => $customer->fresh()]);
    }

    public function redeemLoyaltyPoints(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1|max:' . $customer->loyalty_points,
            'order_id' => 'nullable|exists:orders,id'
        ]);

        $customer->decrement('loyalty_points', $validated['points']);

        // Log loyalty transaction
        DB::table('loyalty_transactions')->insert([
            'customer_id' => $customer->id,
            'points' => -$validated['points'],
            'type' => 'redeemed',
            'reason' => 'Points redeemed for order',
            'order_id' => $validated['order_id'] ?? null,
            'user_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['success' => true, 'customer' => $customer->fresh()]);
    }

    public function loyaltyProgram()
    {
        $restaurant = auth()->user()->restaurant;
        $settings = $restaurant->getSetting('loyalty', 'program', [
            'points_per_dollar' => 1,
            'redemption_rate' => 100, // 100 points = $1
            'bonus_birthday_points' => 50,
            'referral_points' => 100
        ]);

        return response()->json($settings);
    }

    public function updateLoyaltyProgram(Request $request)
    {
        $validated = $request->validate([
            'points_per_dollar' => 'required|numeric|min:0',
            'redemption_rate' => 'required|integer|min:1',
            'bonus_birthday_points' => 'required|integer|min:0',
            'referral_points' => 'required|integer|min:0'
        ]);

        $restaurant = auth()->user()->restaurant;
        app('App\Services\GlobalSettingsService')->set(
            $restaurant->id, 
            'loyalty', 
            'program', 
            $validated, 
            'json'
        );

        return response()->json(['success' => true]);
    }

    public function birthdayCustomers()
    {
        $restaurant = auth()->user()->restaurant;
        $customers = Customer::where('restaurant_id', $restaurant->id)
            ->whereNotNull('date_of_birth')
            ->whereRaw('MONTH(date_of_birth) = ? AND DAY(date_of_birth) = ?', [
                now()->month, now()->day
            ])
            ->get();

        return response()->json($customers);
    }

    public function customerSegments()
    {
        $restaurant = auth()->user()->restaurant;
        
        $segments = [
            'vip' => Customer::where('restaurant_id', $restaurant->id)
                ->where('total_spent', '>=', 1000)->count(),
            'regular' => Customer::where('restaurant_id', $restaurant->id)
                ->where('visit_count', '>=', 5)->count(),
            'new' => Customer::where('restaurant_id', $restaurant->id)
                ->where('visit_count', '<=', 1)->count(),
            'inactive' => Customer::where('restaurant_id', $restaurant->id)
                ->where('last_visit_at', '<', now()->subMonths(3))->count()
        ];

        return response()->json($segments);
    }

    private function getFavoriteItems(Customer $customer)
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.customer_id', $customer->id)
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.name, count(*) as order_count')
            ->orderByDesc('order_count')
            ->take(5)
            ->get();
    }
}