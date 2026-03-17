<?php
$currentModule = $_GET['module'] ?? '';
$currentPage   = basename($_SERVER['PHP_SELF']);

$navItems = [
    ['label' => 'Dashboard',  'icon' => 'layout-dashboard', 'href' => 'dashboard.php',              'page' => 'dashboard.php'],
    ['label' => 'POS',        'icon' => 'monitor',          'href' => 'pos.php',                    'page' => 'pos.php'],
    ['label' => 'Menu',       'icon' => 'book-open',        'href' => 'module.php?module=Menu',     'module' => 'Menu'],
    ['label' => 'Inventory',  'icon' => 'package',          'href' => 'module.php?module=Inventory','module' => 'Inventory'],
    ['label' => 'Customers',  'icon' => 'users',            'href' => 'module.php?module=Customers','module' => 'Customers'],
    ['label' => 'Staff',      'icon' => 'user-check',       'href' => 'module.php?module=Staff',    'module' => 'Staff'],
    ['label' => 'Kitchen',    'icon' => 'chef-hat',         'href' => 'module.php?module=KitchenDisplay', 'module' => 'KitchenDisplay'],
    ['label' => 'Reports',    'icon' => 'bar-chart-2',      'href' => 'module.php?module=Reports',  'module' => 'Reports'],
    ['label' => 'Accounting', 'icon' => 'calculator',       'href' => 'module.php?module=Accounting','module' => 'Accounting'],
    ['label' => 'Settings',   'icon' => 'settings',         'href' => 'settings.php',               'page' => 'settings.php'],
];

function isActive(array $item, string $currentPage, string $currentModule): bool {
    if (isset($item['page']))   return $currentPage === $item['page'];
    if (isset($item['module'])) return $currentModule === $item['module'];
    return false;
}
?>
<!-- Overlay for mobile -->
<div id="sidebarOverlay" onclick="closeSidebar()"
     class="fixed inset-0 bg-black/50 z-20 hidden lg:hidden"></div>

<!-- Sidebar -->
<aside id="sidebar"
       class="fixed left-0 top-0 h-screen w-64 z-30 flex flex-col
              bg-white dark:bg-gray-900
              border-r border-gray-100 dark:border-gray-800
              shadow-lg
              -translate-x-full lg:translate-x-0 transition-transform duration-300">

    <!-- Logo -->
    <div class="flex items-center gap-3 px-5 py-5 border-b border-gray-100 dark:border-gray-800">
        <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center shrink-0">
            <i data-lucide="utensils" class="w-4 h-4 text-white"></i>
        </div>
        <div>
            <span class="text-sm font-bold text-gray-900 dark:text-white block leading-none">QB Restaurant</span>
            <span class="text-[10px] text-gray-400 mt-0.5 block">Management System</span>
        </div>
        <!-- Close btn (mobile) -->
        <button onclick="closeSidebar()" class="ml-auto lg:hidden text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>

    <!-- Nav -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
        <?php foreach ($navItems as $item):
            $active = isActive($item, $currentPage, $currentModule);
        ?>
        <a href="<?php echo htmlspecialchars($item['href']); ?>"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                  <?php echo $active
                      ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400'
                      : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100'; ?>">
            <i data-lucide="<?php echo $item['icon']; ?>"
               class="w-4 h-4 shrink-0 <?php echo $active ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500'; ?>"></i>
            <?php echo htmlspecialchars($item['label']); ?>
            <?php if ($active): ?>
            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- User + Logout -->
    <div class="px-3 py-4 border-t border-gray-100 dark:border-gray-800 space-y-2">
        <div class="flex items-center gap-3 px-3 py-2">
            <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center shrink-0">
                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                </p>
                <p class="text-xs text-gray-400 truncate capitalize">
                    <?php echo htmlspecialchars(str_replace('_', ' ', $_SESSION['user_role'] ?? 'Staff')); ?>
                </p>
            </div>
        </div>
        <a href="logout.php"
           class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-red-600 dark:text-red-400
                  hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
            <i data-lucide="log-out" class="w-4 h-4"></i>
            Sign Out
        </a>
    </div>
</aside>

<!-- Top bar (mobile) -->
<header class="lg:hidden fixed top-0 left-0 right-0 z-10 h-14 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 flex items-center px-4 gap-3">
    <button onclick="openSidebar()" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
        <i data-lucide="menu" class="w-5 h-5 text-gray-600 dark:text-gray-300"></i>
    </button>
    <div class="flex items-center gap-2">
        <div class="w-6 h-6 rounded-lg bg-indigo-600 flex items-center justify-center">
            <i data-lucide="utensils" class="w-3 h-3 text-white"></i>
        </div>
        <span class="text-sm font-bold text-gray-900 dark:text-white">QB Restaurant</span>
    </div>
    <button onclick="toggleTheme()" class="ml-auto p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
        <i data-lucide="sun" class="w-4 h-4 text-gray-600 dark:text-gray-300 hidden dark:block"></i>
        <i data-lucide="moon" class="w-4 h-4 text-gray-600 dark:text-gray-300 block dark:hidden"></i>
    </button>
</header>

<script>
function openSidebar() {
    document.getElementById('sidebar').classList.remove('-translate-x-full');
    document.getElementById('sidebarOverlay').classList.remove('hidden');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.add('-translate-x-full');
    document.getElementById('sidebarOverlay').classList.add('hidden');
}
function toggleTheme() {
    document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
}
if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
</script>
