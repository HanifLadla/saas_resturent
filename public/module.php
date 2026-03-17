<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$module   = preg_replace('/[^a-zA-Z]/', '', $_GET['module'] ?? 'Dashboard');
$viewPath = __DIR__ . "/../Modules/{$module}/Views/index.blade.php";
$titles   = [
    'Menu' => 'Menu Management', 'Inventory' => 'Inventory', 'Customers' => 'Customers',
    'Staff' => 'Staff', 'KitchenDisplay' => 'Kitchen Display', 'Reports' => 'Reports',
    'Accounting' => 'Accounting', 'Subscriptions' => 'Subscriptions',
];
$pageTitle = $titles[$module] ?? $module;
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> — QB Restaurant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>body { font-family: 'Inter', system-ui, sans-serif; }</style>
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-300">

<?php include 'sidebar.php'; ?>

<div class="lg:pl-64">
    <header class="hidden lg:flex items-center justify-between px-6 py-4 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 sticky top-0 z-10">
        <div>
            <h1 class="text-lg font-semibold"><?php echo htmlspecialchars($pageTitle); ?></h1>
        </div>
        <button onclick="toggleTheme()"
            class="p-2 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:shadow-sm transition-all">
            <i data-lucide="sun" class="w-4 h-4 text-gray-500 dark:text-gray-300 hidden dark:block"></i>
            <i data-lucide="moon" class="w-4 h-4 text-gray-500 dark:text-gray-300 block dark:hidden"></i>
        </button>
    </header>

    <main class="p-4 lg:p-6 pt-16 lg:pt-6">
        <?php if (file_exists($viewPath)): ?>
            <?php include $viewPath; ?>
        <?php else: ?>
        <div class="flex flex-col items-center justify-center py-24 text-gray-400">
            <i data-lucide="construction" class="w-12 h-12 mb-4 opacity-40"></i>
            <p class="text-base font-medium text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($pageTitle); ?> module</p>
            <p class="text-sm mt-1">This module view is under construction.</p>
        </div>
        <?php endif; ?>
    </main>
</div>

<script>
    lucide.createIcons();
    if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
</script>
</body>
</html>
