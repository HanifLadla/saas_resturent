<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();
$categories = [];
$products = [];

if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE restaurant_id = ? AND status = 'active'");
    $stmt->execute([$_SESSION['restaurant_id']]);
    $categories = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE restaurant_id = ? AND status = 'active'");
    $stmt->execute([$_SESSION['restaurant_id']]);
    $products = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - QB Restaurant System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100" x-data="posApp()">
    <?php include 'sidebar.php'; ?>
    
    <div class="ml-64 p-6">
        <h2 class="text-2xl font-bold mb-6">Point of Sale</h2>
        
        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold mb-4">Products</h3>
                <div class="grid grid-cols-3 gap-4">
                    <?php foreach ($products as $product): ?>
                    <div @click="addToCart(<?php echo htmlspecialchars(json_encode($product)); ?>)" 
                         class="bg-gray-50 p-4 rounded-lg cursor-pointer hover:bg-gray-100">
                        <h4 class="font-semibold"><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="text-lg font-bold text-blue-600 mt-2">Rs <?php echo number_format($product['price'], 2); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold mb-4">Current Order</h3>
                <div class="space-y-2" x-show="cart.length > 0">
                    <template x-for="(item, index) in cart" :key="index">
                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                            <span x-text="item.name"></span>
                            <span x-text="'Rs ' + (item.price * item.quantity).toFixed(2)"></span>
                        </div>
                    </template>
                </div>
                <div x-show="cart.length === 0" class="text-center text-gray-500 py-8">
                    No items in cart
                </div>
                <div class="mt-4 pt-4 border-t">
                    <div class="flex justify-between font-bold text-lg">
                        <span>Total:</span>
                        <span x-text="'Rs ' + total.toFixed(2)"></span>
                    </div>
                    <button @click="placeOrder()" class="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg">
                        Place Order
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function posApp() {
        return {
            cart: [],
            total: 0,
            addToCart(product) {
                const existing = this.cart.find(item => item.id === product.id);
                if (existing) {
                    existing.quantity++;
                } else {
                    this.cart.push({...product, quantity: 1});
                }
                this.calculateTotal();
            },
            calculateTotal() {
                this.total = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            },
            placeOrder() {
                if (this.cart.length === 0) return;
                alert('Order placed successfully!');
                this.cart = [];
                this.total = 0;
            }
        }
    }
    </script>
</body>
</html>