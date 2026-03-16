<?php

namespace Modules\Staff\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        $query = User::where('restaurant_id', $restaurant->id)->with('branch');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $staff = $query->paginate(20);

        $stats = [
            'total_staff' => User::where('restaurant_id', $restaurant->id)->count(),
            'active_staff' => User::where('restaurant_id', $restaurant->id)->where('is_active', true)->count(),
            'by_role' => User::where('restaurant_id', $restaurant->id)
                ->groupBy('role')
                ->selectRaw('role, count(*) as count')
                ->pluck('count', 'role')
                ->toArray()
        ];

        return response()->json(['staff' => $staff, 'stats' => $stats]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string',
            'role' => ['required', Rule::in(['restaurant_admin', 'branch_manager', 'cashier', 'kitchen_staff', 'accountant'])],
            'branch_id' => 'nullable|exists:branches,id',
            'permissions' => 'nullable|array'
        ]);

        $restaurant = auth()->user()->restaurant;

        // Validate branch belongs to restaurant
        if ($validated['branch_id']) {
            $branch = Branch::where('id', $validated['branch_id'])
                ->where('restaurant_id', $restaurant->id)
                ->firstOrFail();
        }

        $user = User::create([
            'restaurant_id' => $restaurant->id,
            'branch_id' => $validated['branch_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'role' => $validated['role'],
            'permissions' => $validated['permissions'] ?? $this->getDefaultPermissions($validated['role']),
            'is_active' => true
        ]);

        return response()->json(['success' => true, 'user' => $user->load('branch')]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
            'role' => ['required', Rule::in(['restaurant_admin', 'branch_manager', 'cashier', 'kitchen_staff', 'accountant'])],
            'branch_id' => 'nullable|exists:branches,id',
            'permissions' => 'nullable|array'
        ]);

        // Validate branch belongs to restaurant
        if ($validated['branch_id']) {
            Branch::where('id', $validated['branch_id'])
                ->where('restaurant_id', $user->restaurant_id)
                ->firstOrFail();
        }

        $user->update($validated);
        return response()->json(['success' => true, 'user' => $user->load('branch')]);
    }

    public function updateStatus(Request $request, User $user)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $user->update($validated);
        return response()->json(['success' => true, 'user' => $user]);
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed'
        ]);

        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json(['success' => true]);
    }

    public function rolesPermissions()
    {
        $roles = [
            'restaurant_admin' => [
                'label' => 'Restaurant Admin',
                'description' => 'Full access to restaurant management',
                'permissions' => [
                    'manage_staff', 'manage_branches', 'view_reports', 'manage_settings',
                    'manage_menu', 'manage_inventory', 'manage_customers', 'manage_suppliers',
                    'access_pos', 'access_kitchen', 'manage_accounting', 'manage_subscriptions'
                ]
            ],
            'branch_manager' => [
                'label' => 'Branch Manager',
                'description' => 'Manage specific branch operations',
                'permissions' => [
                    'manage_branch_staff', 'view_branch_reports', 'manage_branch_settings',
                    'manage_menu', 'manage_inventory', 'manage_customers',
                    'access_pos', 'access_kitchen', 'view_accounting'
                ]
            ],
            'cashier' => [
                'label' => 'Cashier',
                'description' => 'Handle POS and customer transactions',
                'permissions' => [
                    'access_pos', 'manage_customers', 'process_payments', 'view_orders'
                ]
            ],
            'kitchen_staff' => [
                'label' => 'Kitchen Staff',
                'description' => 'Manage kitchen operations and orders',
                'permissions' => [
                    'access_kitchen', 'update_order_status', 'view_menu', 'manage_inventory'
                ]
            ],
            'accountant' => [
                'label' => 'Accountant',
                'description' => 'Handle financial and accounting tasks',
                'permissions' => [
                    'manage_accounting', 'view_reports', 'manage_suppliers', 'view_orders'
                ]
            ]
        ];

        return response()->json($roles);
    }

    public function updatePermissions(Request $request, User $user)
    {
        $validated = $request->validate([
            'permissions' => 'required|array'
        ]);

        $user->update(['permissions' => $validated['permissions']]);
        return response()->json(['success' => true, 'user' => $user]);
    }

    public function workingHours(Request $request, User $user)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'schedule' => 'required|array',
                'schedule.*.day' => 'required|string',
                'schedule.*.start_time' => 'required|date_format:H:i',
                'schedule.*.end_time' => 'required|date_format:H:i',
                'schedule.*.is_working' => 'required|boolean'
            ]);

            // Store working hours in user settings or separate table
            $user->update(['working_hours' => $validated['schedule']]);
            
            return response()->json(['success' => true]);
        }

        return response()->json($user->working_hours ?? []);
    }

    public function activityLog(User $user)
    {
        // This would typically come from an audit log table
        $activities = [
            ['action' => 'Login', 'timestamp' => now()->subHours(2), 'ip' => '192.168.1.1'],
            ['action' => 'Created Order #12345', 'timestamp' => now()->subHours(1), 'details' => 'Table 5'],
            ['action' => 'Updated Product Price', 'timestamp' => now()->subMinutes(30), 'details' => 'Burger - $12.99']
        ];

        return response()->json($activities);
    }

    private function getDefaultPermissions(string $role): array
    {
        $rolePermissions = [
            'restaurant_admin' => [
                'manage_staff', 'manage_branches', 'view_reports', 'manage_settings',
                'manage_menu', 'manage_inventory', 'manage_customers', 'manage_suppliers',
                'access_pos', 'access_kitchen', 'manage_accounting', 'manage_subscriptions'
            ],
            'branch_manager' => [
                'manage_branch_staff', 'view_branch_reports', 'manage_branch_settings',
                'manage_menu', 'manage_inventory', 'manage_customers',
                'access_pos', 'access_kitchen', 'view_accounting'
            ],
            'cashier' => [
                'access_pos', 'manage_customers', 'process_payments', 'view_orders'
            ],
            'kitchen_staff' => [
                'access_kitchen', 'update_order_status', 'view_menu', 'manage_inventory'
            ],
            'accountant' => [
                'manage_accounting', 'view_reports', 'manage_suppliers', 'view_orders'
            ]
        ];

        return $rolePermissions[$role] ?? [];
    }
}