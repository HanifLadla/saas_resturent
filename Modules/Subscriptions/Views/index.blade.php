@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Subscriptions</h1>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
            New Subscription
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">Active Subscriptions</h3>
            <p class="text-3xl font-bold text-green-600">0</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">Expiring Soon</h3>
            <p class="text-3xl font-bold text-orange-600">0</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">Monthly Revenue</h3>
            <p class="text-3xl font-bold text-blue-600">$0.00</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold">Subscription Plans</h2>
        </div>
        <div class="p-6">
            <div class="text-center py-8 text-gray-500">
                No subscription plans found
            </div>
        </div>
    </div>
</div>
@endsection