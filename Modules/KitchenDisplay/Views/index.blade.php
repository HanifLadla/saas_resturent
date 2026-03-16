@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Kitchen Display</h1>
        <div class="flex space-x-2">
            <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                Start Display
            </button>
            <button class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                Settings
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">Pending Orders</h3>
            <p class="text-3xl font-bold text-orange-600">0</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">In Progress</h3>
            <p class="text-3xl font-bold text-blue-600">0</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">Completed Today</h3>
            <p class="text-3xl font-bold text-green-600">0</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold">Kitchen Orders</h2>
        </div>
        <div class="p-6">
            <div class="text-center py-8 text-gray-500">
                No orders in kitchen queue
            </div>
        </div>
    </div>
</div>
@endsection