<!DOCTYPE html>
<html lang="en" x-data="customers()" x-init="init()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - QB Restaurant System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Customer Management</h1>
            <button @click="showAddModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-lg">
                Add Customer
            </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm text-gray-600">Total Customers</h3>
                <p class="text-2xl font-bold text-gray-900" x-text="stats.total_customers"></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm text-gray-600">VIP Customers</h3>
                <p class="text-2xl font-bold text-purple-600" x-text="stats.vip_customers"></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm text-gray-600">New This Month</h3>
                <p class="text-2xl font-bold text-green-600" x-text="stats.new_this_month"></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm text-gray-600">Avg Loyalty Points</h3>
                <p class="text-2xl font-bold text-blue-600" x-text="Math.round(stats.avg_loyalty_points)"></p>
            </div>
        </div>

        <!-- Customer Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">Name</th>
                                <th class="text-left py-2">Email</th>
                                <th class="text-left py-2">Phone</th>
                                <th class="text-left py-2">Total Spent</th>
                                <th class="text-left py-2">Loyalty Points</th>
                                <th class="text-left py-2">Visit Count</th>
                                <th class="text-left py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="customer in customers" :key="customer.id">
                                <tr class="border-b">
                                    <td class="py-2" x-text="customer.name"></td>
                                    <td class="py-2" x-text="customer.email || 'N/A'"></td>
                                    <td class="py-2" x-text="customer.phone || 'N/A'"></td>
                                    <td class="py-2" x-text="'$' + customer.total_spent"></td>
                                    <td class="py-2" x-text="customer.loyalty_points"></td>
                                    <td class="py-2" x-text="customer.visit_count"></td>
                                    <td class="py-2">
                                        <button @click="viewCustomer(customer)" class="text-blue-600 hover:text-blue-800">
                                            View
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
        function customers() {
            return {
                customers: [],
                stats: {},
                showAddModal: false,

                async init() {
                    await this.loadCustomers();
                },

                async loadCustomers() {
                    const response = await fetch('/customers');
                    const data = await response.json();
                    this.customers = data.customers.data || [];
                    this.stats = data.stats || {};
                },

                viewCustomer(customer) {
                    window.location.href = `/customers/${customer.id}`;
                }
            }
        }
    </script>
</body>
</html>