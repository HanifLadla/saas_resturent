<?php
$currentModule = $_GET['module'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);

$menuItems = [
    ['name' => 'Dashboard', 'icon' => '📊', 'url' => 'dashboard.php', 'page' => 'dashboard.php'],
    ['name' => 'POS', 'icon' => '💳', 'url' => 'module.php?module=POS', 'module' => 'POS'],
    ['name' => 'Menu', 'icon' => '🍽️', 'url' => 'module.php?module=Menu', 'module' => 'Menu'],
    ['name' => 'Inventory', 'icon' => '📦', 'url' => 'module.php?module=Inventory', 'module' => 'Inventory'],
    ['name' => 'Customers', 'icon' => '👥', 'url' => 'module.php?module=Customers', 'module' => 'Customers'],
    ['name' => 'Staff', 'icon' => '👨‍💼', 'url' => 'module.php?module=Staff', 'module' => 'Staff'],
    ['name' => 'Kitchen', 'icon' => '🍳', 'url' => 'module.php?module=KitchenDisplay', 'module' => 'KitchenDisplay'],
    ['name' => 'Reports', 'icon' => '📈', 'url' => 'module.php?module=Reports', 'module' => 'Reports'],
    ['name' => 'Accounting', 'icon' => '💰', 'url' => 'module.php?module=Accounting', 'module' => 'Accounting'],
    ['name' => 'Settings', 'icon' => '⚙️', 'url' => 'settings.php', 'page' => 'settings.php'],
];

function isActive($item, $currentPage, $currentModule) {
    if (isset($item['page'])) {
        return $currentPage === $item['page'];
    }
    if (isset($item['module'])) {
        return $currentModule === $item['module'];
    }
    return false;
}
?>
<style>
@keyframes slideIn {
    from { transform: translateX(-100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.sidebar-item {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    animation: slideIn 0.3s ease-out forwards;
}
.sidebar-item:hover {
    transform: translateX(4px);
}
.sidebar-item.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}
.sidebar-logo {
    animation: fadeIn 0.5s ease-out;
}
</style>

<aside class="fixed left-0 top-0 w-64 h-screen bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 text-white flex flex-col shadow-2xl">
    <div class="p-6 border-b border-gray-700 sidebar-logo">
        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-400 to-purple-500 bg-clip-text text-transparent">QB Restaurant</h1>
        <p class="text-xs text-gray-400 mt-1">Management System</p>
    </div>
    
    <nav class="flex-1 overflow-y-auto p-4 space-y-1">
        <?php foreach ($menuItems as $index => $item): 
            $active = isActive($item, $currentPage, $currentModule);
        ?>
        <a href="<?php echo htmlspecialchars($item['url']); ?>" 
           class="sidebar-item flex items-center px-4 py-3 rounded-lg <?php echo $active ? 'active' : 'hover:bg-gray-800'; ?>"
           style="animation-delay: <?php echo $index * 0.05; ?>s;">
            <span class="text-2xl mr-3"><?php echo $item['icon']; ?></span>
            <span class="font-medium"><?php echo htmlspecialchars($item['name']); ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="p-4 border-t border-gray-700 bg-gray-900">
        <div class="flex items-center space-x-3 mb-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
            </div>
            <div class="flex-1">
                <div class="text-sm font-medium"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Role'); ?></div>
            </div>
        </div>
        <a href="logout.php" class="flex items-center justify-center w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg transition-all duration-300 hover:shadow-lg">
            <span class="mr-2">🚪</span>
            <span>Logout</span>
        </a>
    </div>
</aside>