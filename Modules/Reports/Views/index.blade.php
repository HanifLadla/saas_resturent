@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Reports</h1>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
            Generate Report
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">Sales Reports</h3>
            <p class="text-sm text-gray-500">Daily, Weekly, Monthly</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">Inventory Reports</h3>
            <p class="text-sm text-gray-500">Stock levels, Usage</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">Financial Reports</h3>
            <p class="text-sm text-gray-500">P&L, Balance Sheet</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">Staff Reports</h3>
            <p class="text-sm text-gray-500">Performance, Hours</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold">Recent Reports</h2>
        </div>
        <div class="p-6">
            <div class="text-center py-8 text-gray-500">
                No reports generated yet
            </div>
        </div>
    </div>
</div>
@endsection