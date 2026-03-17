<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$pdo = getDBConnection();
$categories = [];
$products    = [];

if ($pdo) {
    $rid = $_SESSION['restaurant_id'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE restaurant_id=? AND status='active' ORDER BY name");
    $stmt->execute([$rid]); $categories = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.restaurant_id=? AND p.status='active' ORDER BY p.name");
    $stmt->execute([$rid]); $products = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS — QB Restaurant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        [x-cloak] { display: none !important; }
        .product-card { transition: transform 0.1s, box-shadow 0.1s; }
        .product-card:hover { transform: translateY(-1px); }
        .product-card:active { transform: scale(0.98); }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-300"
      x-data="posApp()" x-cloak>

<?php include 'sidebar.php'; ?>

<div class="lg:pl-64 flex flex-col h-screen">

    <!-- Top bar -->
    <header class="hidden lg:flex items-center justify-between px-6 py-4 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 shrink-0">
        <div>
            <h1 class="text-lg font-semibold">Point of Sale</h1>
            <p class="text-xs text-gray-400 mt-0.5">Select items and process orders</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="toggleTheme()"
                class="p-2 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:shadow-sm transition-all">
                <i data-lucide="sun" class="w-4 h-4 text-gray-500 dark:text-gray-300 hidden dark:block"></i>
                <i data-lucide="moon" class="w-4 h-4 text-gray-500 dark:text-gray-300 block dark:hidden"></i>
            </button>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden pt-14 lg:pt-0">

        <!-- Products panel -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Search + category filter -->
            <div class="px-4 py-3 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 space-y-3">
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <input type="text" x-model="search" placeholder="Search products..."
                           class="w-full pl-9 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>
                <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
                    <button @click="activeCategory = null"
                            :class="activeCategory === null ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                            class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                        All
                    </button>
                    <?php foreach ($categories as $cat): ?>
                    <button @click="activeCategory = <?php echo $cat['id']; ?>"
                            :class="activeCategory === <?php echo $cat['id']; ?> ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                            class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Product grid -->
            <div class="flex-1 overflow-y-auto p-4">
                <?php if (empty($products)): ?>
                <div class="flex flex-col items-center justify-center h-full text-gray-400">
                    <i data-lucide="package-open" class="w-12 h-12 mb-3 opacity-40"></i>
                    <p class="text-sm">No products found</p>
                    <a href="module.php?module=Menu" class="mt-2 text-xs text-indigo-600 hover:underline">Add products to menu</a>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                    <?php foreach ($products as $p): ?>
                    <template x-if="filteredProducts().some(fp => fp.id === <?php echo $p['id']; ?>)">
                        <div @click="addToCart(<?php echo htmlspecialchars(json_encode(['id' => $p['id'], 'name' => $p['name'], 'price' => (float)$p['price'], 'category_id' => $p['category_id']])); ?>)"
                             class="product-card bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-3 cursor-pointer shadow-sm hover:shadow-md hover:border-indigo-200 dark:hover:border-indigo-700">
                            <div class="w-full aspect-square rounded-lg bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center mb-3">
                                <i data-lucide="utensils" class="w-6 h-6 text-indigo-400"></i>
                            </div>
                            <p class="text-xs font-semibold text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($p['name']); ?></p>
                            <p class="text-xs text-gray-400 truncate"><?php echo htmlspecialchars($p['category_name'] ?? ''); ?></p>
                            <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400 mt-1">Rs <?php echo number_format($p['price'], 2); ?></p>
                        </div>
                    </template>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order panel -->
        <div class="w-80 xl:w-96 shrink-0 flex flex-col bg-white dark:bg-gray-900 border-l border-gray-100 dark:border-gray-800">

            <!-- Order header -->
            <div class="px-4 py-4 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold">Current Order</h2>
                    <button @click="clearCart()" x-show="cart.length > 0"
                            class="text-xs text-red-500 hover:text-red-600 flex items-center gap-1">
                        <i data-lucide="trash-2" class="w-3 h-3"></i> Clear
                    </button>
                </div>
                <!-- Order type -->
                <div class="flex gap-1">
                    <?php foreach (['dine_in' => 'Dine In', 'takeaway' => 'Takeaway', 'delivery' => 'Delivery'] as $val => $lbl): ?>
                    <button @click="orderType = '<?php echo $val; ?>'"
                            :class="orderType === '<?php echo $val; ?>' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300'"
                            class="flex-1 py-1.5 rounded-lg text-xs font-medium transition-colors">
                        <?php echo $lbl; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cart items -->
            <div class="flex-1 overflow-y-auto px-4 py-3 space-y-2">
                <template x-if="cart.length === 0">
                    <div class="flex flex-col items-center justify-center h-full text-gray-400 py-12">
                        <i data-lucide="shopping-cart" class="w-10 h-10 mb-3 opacity-30"></i>
                        <p class="text-sm">Cart is empty</p>
                        <p class="text-xs mt-1">Tap a product to add it</p>
                    </div>
                </template>
                <template x-for="(item, i) in cart" :key="i">
                    <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 rounded-xl p-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                            <i data-lucide="utensils" class="w-3.5 h-3.5 text-indigo-500"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium truncate" x-text="item.name"></p>
                            <p class="text-xs text-gray-400" x-text="'Rs ' + item.price.toFixed(2)"></p>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button @click="decrement(i)"
                                    class="w-6 h-6 rounded-md bg-gray-200 dark:bg-gray-700 flex items-center justify-center hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                <i data-lucide="minus" class="w-3 h-3"></i>
                            </button>
                            <span class="text-xs font-bold w-5 text-center" x-text="item.quantity"></span>
                            <button @click="increment(i)"
                                    class="w-6 h-6 rounded-md bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center hover:bg-indigo-200 dark:hover:bg-indigo-900/60 transition-colors">
                                <i data-lucide="plus" class="w-3 h-3 text-indigo-600 dark:text-indigo-400"></i>
                            </button>
                        </div>
                        <p class="text-xs font-bold text-gray-900 dark:text-white w-16 text-right shrink-0"
                           x-text="'Rs ' + (item.price * item.quantity).toFixed(2)"></p>
                    </div>
                </template>
            </div>

            <!-- Order summary + checkout -->
            <div class="px-4 py-4 border-t border-gray-100 dark:border-gray-800 space-y-3">
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between text-gray-500 dark:text-gray-400">
                        <span>Subtotal</span>
                        <span x-text="'Rs ' + subtotal().toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-gray-500 dark:text-gray-400">
                        <span>Tax (0%)</span>
                        <span>Rs 0.00</span>
                    </div>
                    <div class="flex justify-between font-bold text-base text-gray-900 dark:text-white pt-1 border-t border-gray-100 dark:border-gray-800">
                        <span>Total</span>
                        <span x-text="'Rs ' + subtotal().toFixed(2)"></span>
                    </div>
                </div>

                <!-- Payment method -->
                <div class="grid grid-cols-3 gap-1.5">
                    <?php foreach (['cash' => 'Cash', 'card' => 'Card', 'wallet' => 'Wallet'] as $val => $lbl): ?>
                    <button @click="paymentMethod = '<?php echo $val; ?>'"
                            :class="paymentMethod === '<?php echo $val; ?>' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700'"
                            class="py-2 rounded-lg text-xs font-medium border transition-colors">
                        <?php echo $lbl; ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <button @click="placeOrder()"
                        :disabled="cart.length === 0"
                        :class="cart.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-indigo-700'"
                        class="w-full bg-indigo-600 text-white font-semibold py-3 rounded-xl transition-colors flex items-center justify-center gap-2 text-sm">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <span x-text="'Place Order — Rs ' + subtotal().toFixed(2)"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success toast -->
<div x-show="toast" x-transition
     class="fixed bottom-6 right-6 bg-emerald-600 text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-2 text-sm z-50">
    <i data-lucide="check-circle" class="w-4 h-4"></i>
    <span x-text="toastMsg"></span>
</div>

<script>
lucide.createIcons();
if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');

const allProducts = <?php echo json_encode(array_map(fn($p) => [
    'id' => $p['id'], 'name' => $p['name'],
    'price' => (float)$p['price'], 'category_id' => $p['category_id']
], $products)); ?>;

function posApp() {
    return {
        cart: [],
        search: '',
        activeCategory: null,
        orderType: 'dine_in',
        paymentMethod: 'cash',
        toast: false,
        toastMsg: '',

        filteredProducts() {
            return allProducts.filter(p => {
                const matchCat = this.activeCategory === null || p.category_id == this.activeCategory;
                const matchSearch = !this.search || p.name.toLowerCase().includes(this.search.toLowerCase());
                return matchCat && matchSearch;
            });
        },

        addToCart(product) {
            const existing = this.cart.find(i => i.id === product.id);
            if (existing) { existing.quantity++; }
            else { this.cart.push({...product, quantity: 1}); }
            lucide.createIcons();
        },

        increment(i) { this.cart[i].quantity++; },

        decrement(i) {
            if (this.cart[i].quantity > 1) { this.cart[i].quantity--; }
            else { this.cart.splice(i, 1); }
        },

        clearCart() { this.cart = []; },

        subtotal() {
            return this.cart.reduce((s, i) => s + i.price * i.quantity, 0);
        },

        placeOrder() {
            if (!this.cart.length) return;
            this.showToast('Order placed successfully!');
            this.cart = [];
        },

        showToast(msg) {
            this.toastMsg = msg;
            this.toast = true;
            setTimeout(() => this.toast = false, 3000);
        }
    };
}
</script>
</body>
</html>
