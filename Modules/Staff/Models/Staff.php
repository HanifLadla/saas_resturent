<?php

namespace Modules\Staff\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Staff extends Model
{
    public static function getStaffByRole($restaurantId)
    {
        return User::where('restaurant_id', $restaurantId)
            ->groupBy('role')
            ->selectRaw('role, count(*) as count')
            ->pluck('count', 'role')
            ->toArray();
    }
    
    public static function getActiveStaff($restaurantId)
    {
        return User::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->with('branch')
            ->get();
    }
    
    public static function getRolePermissions()
    {
        return [
            'restaurant_admin' => ['manage_staff', 'manage_branches', 'view_reports', 'manage_settings'],
            'branch_manager' => ['manage_branch_staff', 'view_branch_reports', 'manage_menu'],
            'cashier' => ['access_pos', 'manage_customers', 'process_payments'],
            'kitchen_staff' => ['access_kitchen', 'update_order_status'],
            'accountant' => ['manage_accounting', 'view_reports']
        ];
    }
}