<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['email' => 'Invalid credentials']);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'restaurant_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
        ]);

        DB::transaction(function () use ($request) {
            $restaurant = Restaurant::create([
                'name' => $request->restaurant_name,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->email,
                'status' => 'active',
                'subscription_status' => 'trial',
                'subscription_expires_at' => now()->addDays(30),
            ]);

            $user = User::create([
                'name' => $request->owner_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'restaurant_id' => $restaurant->id,
                'role' => 'restaurant_admin',
            ]);

            Auth::login($user);
        });

        return redirect('/dashboard')->with('success', 'Restaurant registered successfully!');
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
}