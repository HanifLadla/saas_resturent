<!DOCTYPE html>
<html lang="en" x-data="dashboard()" x-init="init()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - QB Restaurant System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b px-6 py-4">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600">{{ auth()->user()->restaurant->name }}</span>
                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                    <span class="text-white text-sm font-medium">{{ substr(auth()->user()->name, 0, 1) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-dollar-sign text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Today's Sales</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="'$' + stats.today_sales"></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-shopping-cart text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Orders</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.today_orders"></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i class="fas fa-users text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Customers</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_customers"></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Low Stock</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.low_stock_items"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-semibold text-gray-800">Recent Orders</h3>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">Order #</th>
                                <th class="text-left py-2">Customer</th>
                                <th class="text-left py-2">Type</th>
                                <th class="text-left py-2">Status</th>
                                <th class="text-left py-2">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="order in recentOrders" :key="order.id">
                                <tr class="border-b">
                                    <td class="py-2" x-text="order.order_number"></td>
                                    <td class="py-2" x-text="order.customer?.name || 'Walk-in'"></td>
                                    <td class="py-2" x-text="order.type"></td>
                                    <td class="py-2">
                                        <span class="px-2 py-1 rounded-full text-xs" 
                                              :class="getStatusColor(order.status)"
                                              x-text="order.status"></span>
                                    </td>
                                    <td class="py-2" x-text="'$' + order.total_amount"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function dashboard() {
            return {
                stats: {
                    today_sales: 0,
                    today_orders: 0,
                    total_customers: 0,
                    low_stock_items: 0
                },
                recentOrders: [],

                async init() {
                    await this.loadStats();
                    await this.loadRecentOrders();
                },

                async loadStats() {
                    try {
                        const response = await fetch('/dashboard/stats');
                        this.stats = await response.json();
                    } catch (error) {
                        console.error('Failed to load stats:', error);
                    }
                },

                async loadRecentOrders() {
                    try {
                        const response = await fetch('/dashboard/recent-orders');
                        this.recentOrders = await response.json();
                    } catch (error) {
                        console.error('Failed to load orders:', error);
                    }
                },

                getStatusColor(status) {
                    const colors = {
                        'pending': 'bg-yellow-100 text-yellow-800',
                        'confirmed': 'bg-blue-100 text-blue-800',
                        'preparing': 'bg-orange-100 text-orange-800',
                        'ready': 'bg-green-100 text-green-800',
                        'completed': 'bg-gray-100 text-gray-800'
                    };
                    return colors[status] || 'bg-gray-100 text-gray-800';
                }
            }
        }
    </script>
</body>
</html>