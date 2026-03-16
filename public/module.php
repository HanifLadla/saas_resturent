<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$module = $_GET['module'] ?? 'dashboard';
$viewPath = __DIR__ . "/../Modules/" . ucfirst($module) . "/Views/index.blade.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($module); ?> - QB Restaurant System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>
    
    <div class="ml-64 p-6">
        <?php
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Module not found</div>';
        }
        ?>
    </div>
</body>
</html>
?>