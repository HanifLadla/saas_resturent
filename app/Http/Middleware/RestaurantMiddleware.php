<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestaurantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user || !$user->restaurant) {
            return redirect('/login');
        }

        if (!$user->restaurant->isActive()) {
            return response()->json(['error' => 'Restaurant subscription expired'], 403);
        }

        return $next($request);
    }
}

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!auth()->user() || auth()->user()->role !== $role) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = auth()->user();
        
        if (!$user || !in_array($permission, $user->permissions ?? [])) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        return $next($request);
    }
}

class SettingsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $restaurant = auth()->user()->restaurant;
        $settingsService = app('App\Services\GlobalSettingsService');
        
        // Load and cache restaurant settings
        $settings = $settingsService->getCategory($restaurant->id, 'app');
        config(['restaurant.settings' => $settings]);
        
        return $next($request);
    }
}