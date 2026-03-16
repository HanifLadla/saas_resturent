<?php

namespace Modules\Staff\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StaffService
{
    public function createUser($data)
    {
        return User::create([
            'restaurant_id' => auth()->user()->restaurant->id,
            'password' => Hash::make($data['password']),
            'permissions' => $this->getDefaultPermissions($data['role']),
            ...$data
        ]);
    }

    public function updatePermissions(User $user, $permissions)
    {
        $user->update(['permissions' => $permissions]);
    }

    private function getDefaultPermissions($role)
    {
        $rolePermissions = [
            'restaurant_admin' => ['manage_staff', 'manage_settings', 'view_reports'],
            'branch_manager' => ['manage_branch_staff', 'view_branch_reports'],
            'cashier' => ['access_pos', 'manage_customers'],
            'kitchen_staff' => ['access_kitchen', 'update_order_status'],
            'accountant' => ['manage_accounting', 'view_reports']
        ];

        return $rolePermissions[$role] ?? [];
    }
}