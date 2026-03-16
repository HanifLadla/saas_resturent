<!DOCTYPE html>
<html lang="en" x-data="inventory()" x-init="init()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - QB Restaurant System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Inventory Management</h1>
            <button @click="showAddModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-lg">
                Add Item
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm text-gray-600">Total Items</h3>
                <p class="text-2xl font-bold text-gray-900" x-text="stats.total_items"></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm text-gray-600">Low Stock</h3>
                <p class="text-2xl font-bold text-red-600" x-text="stats.low_stock_items"></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm text-gray-600">Out of Stock</h3>
                <p class="text-2xl font-bold text-red-800" x-text="stats.out_of_stock"></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm text-gray-600">Pending Orders</h3>
                <p class="text-2xl font-bold text-yellow-600" x-text="stats.pending_orders"></p>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">Item Name</th>
                                <th class="text-left py-2">SKU</th>
                                <th class="text-left py-2">Current Stock</th>
                                <th class="text-left py-2">Min Stock</th>
                                <th class="text-left py-2">Unit Cost</th>
                                <th class="text-left py-2">Status</th>
                                <th class="text-left py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in items" :key="item.id">
                                <tr class="border-b">
                                    <td class="py-2" x-text="item.name"></td>
                                    <td class="py-2" x-text="item.sku"></td>
                                    <td class="py-2" x-text="item.current_stock"></td>
                                    <td class="py-2" x-text="item.minimum_stock"></td>
                                    <td class="py-2" x-text="'$' + item.unit_cost"></td>
                                    <td class="py-2">
                                        <span class="px-2 py-1 rounded-full text-xs"
                                              :class="getStatusColor(item)"
                                              x-text="getStatusText(item)"></span>
                                    </td>
                                    <td class="py-2">
                                        <button @click="adjustStock(item)" class="text-blue-600 hover:text-blue-800">
                                            Adjust
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function inventory() {
            return {
                items: [],
                stats: {},
                showAddModal: false,

                async init() {
                    await this.loadItems();
                    await this.loadStats();
                },

                async loadItems() {
                    const response = await fetch('/inventory/items');
                    const data = await response.json();
                    this.items = data.data || [];
                },

                async loadStats() {
                    const response = await fetch('/inventory');
                    this.stats = await response.json();
                },

                getStatusColor(item) {
                    if (item.current_stock <= 0) return 'bg-red-100 text-red-800';
                    if (item.current_stock <= item.minimum_stock) return 'bg-yellow-100 text-yellow-800';
                    return 'bg-green-100 text-green-800';
                },

                getStatusText(item) {
                    if (item.current_stock <= 0) return 'Out of Stock';
                    if (item.current_stock <= item.minimum_stock) return 'Low Stock';
                    return 'In Stock';
                },

                adjustStock(item) {
                    // Stock adjustment logic
                }
            }
        }
    </script>
</body>
</html>