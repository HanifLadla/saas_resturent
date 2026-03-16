@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">API Management</h1>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
            Generate API Key
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">API Requests Today</h3>
            <p class="text-3xl font-bold text-blue-600">0</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">Active API Keys</h3>
            <p class="text-3xl font-bold text-green-600">0</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold">API Keys</h2>
        </div>
        <div class="p-6">
            <div class="text-center py-8 text-gray-500">
                No API keys found
            </div>
        </div>
    </div>
</div>
@endsection