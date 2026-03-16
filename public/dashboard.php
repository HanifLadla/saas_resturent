<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - QB Restaurant System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>
    
    <div class="ml-64">
        <header class="bg-white shadow-sm border-b">
            <div class="px-6 py-4">
                <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
            </div>
        </header>

        <main class="p-6">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                Welcome to QB Restaurant System!
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-700">Total Orders</h3>
                    <p class="text-3xl font-bold text-blue-600">0</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-700">Total Revenue</h3>
                    <p class="text-3xl font-bold text-green-600">Rs 0.00</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-700">Total Products</h3>
                    <p class="text-3xl font-bold text-purple-600">0</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-700">Total Customers</h3>
                    <p class="text-3xl font-bold text-orange-600">0</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-semibold mb-4">Recent Orders</h3>
                    <p class="text-gray-500">No recent orders</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-semibold mb-4">Quick Actions</h3>
                    <div class="space-y-2">
                        <a href="module.php?module=POS" class="block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center">New Order</a>
                        <a href="module.php?module=Menu" class="block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-center">Manage Menu</a>
                        <a href="module.php?module=Reports" class="block bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-center">View Reports</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>