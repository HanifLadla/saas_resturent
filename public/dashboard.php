<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

require_once 'config.php';
$pdo = getDBConnection();

// Stats
$stats = ['orders' => 0, 'revenue' => 0, 'products' => 0, 'customers' => 0, 'pending' => 0, 'avg_order' => 0];
$recentOrders = [];
$topProducts  = [];

if ($pdo) {
    $rid = $_SESSION['restaurant_id'];
    $stats['orders']    = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id=?");
    $stats['orders']->execute([$rid]); $stats['orders'] = (int)$stats['orders']->fetchColumn();

    $stats['revenue']   = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE restaurant_id=? AND payment_status='paid'");
    $stats['revenue']->execute([$rid]); $stats['revenue'] = (float)$stats['revenue']->fetchColumn();

    $stats['products']  = $pdo->prepare("SELECT COUNT(*) FROM products WHERE restaurant_id=?");
    $stats['products']->execute([$rid]); $stats['products'] = (int)$stats['products']->fetchColumn();

    $stats['customers'] = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE restaurant_id=?");
    $stats['customers']->execute([$rid]); $stats['customers'] = (int)$stats['customers']->fetchColumn();

    $stats['pending']   = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id=? AND status='pending'");
    $stats['pending']->execute([$rid]); $stats['pending'] = (int)$stats['pending']->fetchColumn();

    $stats['avg_order'] = $stats['orders'] > 0 ? round($stats['revenue'] / $stats['orders'], 2) : 0;

    $q = $pdo->prepare("SELECT * FROM orders WHERE restaurant_id=? ORDER BY created_at DESC LIMIT 8");
    $q->execute([$rid]); $recentOrders = $q->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — QB Restaurant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .stat-card { transition: transform 0.15s, box-shadow 0.15s; }
        .stat-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-300">

<?php include 'sidebar.php'; ?>

<!-- Main content -->
<div class="lg:pl-64">
    <!-- Top bar (desktop) -->
    <header class="hidden lg:flex items-center justify-between px-6 py-4 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 sticky top-0 z-10">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Dashboard</h1>
            <p class="text-xs text-gray-400 mt-0.5">
                <?php echo date('l, F j, Y'); ?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="toggleTheme()"
                class="p-2 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:shadow-sm transition-all">
                <i data-lucide="sun" class="w-4 h-4 text-gray-500 dark:text-gray-300 hidden dark:block"></i>
                <i data-lucide="moon" class="w-4 h-4 text-gray-500 dark:text-gray-300 block dark:hidden"></i>
            </button>
            <a href="pos.php"
               class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i>
                New Order
            </a>
        </div>
    </header>

    <main class="p-4 lg:p-6 pt-16 lg:pt-6 space-y-6">

        <!-- Welcome banner -->
        <div class="bg-gradient-to-r from-indigo-600 to-violet-600 rounded-2xl p-5 text-white flex items-center justify-between">
            <div>
                <p class="text-indigo-200 text-sm">Good <?php echo (date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening')); ?>,</p>
                <h2 class="text-xl font-bold mt-0.5"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></h2>
                <p class="text-indigo-200 text-sm mt-1">Here's what's happening at your restaurant today.</p>
            </div>
            <div class="hidden sm:block opacity-20">
                <i data-lucide="utensils" class="w-16 h-16"></i>
            </div>
        </div>

        <!-- Stats grid -->
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            <?php
            $cards = [
                ['label' => 'Total Orders',    'value' => $stats['orders'],                        'icon' => 'shopping-bag',  'color' => 'indigo'],
                ['label' => 'Revenue',         'value' => 'Rs ' . number_format($stats['revenue'], 2), 'icon' => 'banknote',  'color' => 'emerald'],
                ['label' => 'Products',        'value' => $stats['products'],                      'icon' => 'package',       'color' => 'violet'],
                ['label' => 'Customers',       'value' => $stats['customers'],                     'icon' => 'users',         'color' => 'sky'],
                ['label' => 'Pending Orders',  'value' => $stats['pending'],                       'icon' => 'clock',         'color' => 'amber'],
                ['label' => 'Avg. Order',      'value' => 'Rs ' . number_format($stats['avg_order'], 2), 'icon' => 'trending-up', 'color' => 'rose'],
            ];
            $colorMap = [
                'indigo'  => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/20',  'icon' => 'text-indigo-600 dark:text-indigo-400',  'val' => 'text-indigo-700 dark:text-indigo-300'],
                'emerald' => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/20','icon' => 'text-emerald-600 dark:text-emerald-400','val' => 'text-emerald-700 dark:text-emerald-300'],
                'violet'  => ['bg' => 'bg-violet-50 dark:bg-violet-900/20',  'icon' => 'text-violet-600 dark:text-violet-400',  'val' => 'text-violet-700 dark:text-violet-300'],
                'sky'     => ['bg' => 'bg-sky-50 dark:bg-sky-900/20',        'icon' => 'text-sky-600 dark:text-sky-400',        'val' => 'text-sky-700 dark:text-sky-300'],
                'amber'   => ['bg' => 'bg-amber-50 dark:bg-amber-900/20',    'icon' => 'text-amber-600 dark:text-amber-400',    'val' => 'text-amber-700 dark:text-amber-300'],
                'rose'    => ['bg' => 'bg-rose-50 dark:bg-rose-900/20',      'icon' => 'text-rose-600 dark:text-rose-400',      'val' => 'text-rose-700 dark:text-rose-300'],
            ];
            foreach ($cards as $card):
                $c = $colorMap[$card['color']];
            ?>
            <div class="stat-card bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400"><?php echo $card['label']; ?></p>
                    <div class="w-8 h-8 rounded-lg <?php echo $c['bg']; ?> flex items-center justify-center">
                        <i data-lucide="<?php echo $card['icon']; ?>" class="w-4 h-4 <?php echo $c['icon']; ?>"></i>
                    </div>
                </div>
                <p class="text-xl font-bold <?php echo $c['val']; ?>"><?php echo $card['value']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Bottom grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Recent Orders -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Orders</h3>
                    <a href="module.php?module=Reports" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                        View all <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800">
                    <?php if (empty($recentOrders)): ?>
                    <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                        <i data-lucide="inbox" class="w-10 h-10 mb-3 opacity-40"></i>
                        <p class="text-sm">No orders yet</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($recentOrders as $order):
                        $statusColors = [
                            'pending'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                            'confirmed' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                            'preparing' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
                            'ready'     => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
                            'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                            'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                        ];
                        $sc = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-600';
                    ?>
                    <div class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-800 flex items-center justify-center">
                                <i data-lucide="receipt" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?>
                                </p>
                                <p class="text-xs text-gray-400">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['type'] ?? 'dine_in')); ?>
                                    &middot; <?php echo date('M j, g:i a', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                Rs <?php echo number_format($order['total_amount'] ?? 0, 2); ?>
                            </span>
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full <?php echo $sc; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Quick Actions</h3>
                </div>
                <div class="p-4 space-y-2">
                    <?php
                    $actions = [
                        ['label' => 'New Order',      'icon' => 'plus-circle',  'href' => 'pos.php',                     'color' => 'indigo'],
                        ['label' => 'Manage Menu',    'icon' => 'book-open',    'href' => 'module.php?module=Menu',      'color' => 'emerald'],
                        ['label' => 'View Inventory', 'icon' => 'package',      'href' => 'module.php?module=Inventory', 'color' => 'violet'],
                        ['label' => 'Kitchen Display','icon' => 'chef-hat',     'href' => 'module.php?module=KitchenDisplay', 'color' => 'amber'],
                        ['label' => 'Reports',        'icon' => 'bar-chart-2',  'href' => 'module.php?module=Reports',   'color' => 'sky'],
                        ['label' => 'Accounting',     'icon' => 'calculator',   'href' => 'module.php?module=Accounting','color' => 'rose'],
                    ];
                    $btnColors = [
                        'indigo'  => 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/40',
                        'emerald' => 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/40',
                        'violet'  => 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300 hover:bg-violet-100 dark:hover:bg-violet-900/40',
                        'amber'   => 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/40',
                        'sky'     => 'bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-300 hover:bg-sky-100 dark:hover:bg-sky-900/40',
                        'rose'    => 'bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/40',
                    ];
                    foreach ($actions as $a):
                    ?>
                    <a href="<?php echo $a['href']; ?>"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo $btnColors[$a['color']]; ?>">
                        <i data-lucide="<?php echo $a['icon']; ?>" class="w-4 h-4 shrink-0"></i>
                        <?php echo $a['label']; ?>
                        <i data-lucide="chevron-right" class="w-3 h-3 ml-auto opacity-50"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
    lucide.createIcons();
    if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
</script>
</body>
</html>
