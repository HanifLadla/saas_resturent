<?php
session_start();
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — QB Restaurant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .input-field {
            @apply w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700
                   bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                   focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                   transition text-sm;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-950 flex items-center justify-center transition-colors duration-300">

    <!-- Dark mode toggle -->
    <button id="themeToggle" onclick="toggleTheme()"
        class="fixed top-4 right-4 p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all">
        <i data-lucide="sun" class="w-4 h-4 text-gray-600 dark:text-gray-300 hidden dark:block"></i>
        <i data-lucide="moon" class="w-4 h-4 text-gray-600 dark:text-gray-300 block dark:hidden"></i>
    </button>

    <div class="w-full max-w-md px-4">
        <!-- Card -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 p-8">

            <!-- Logo -->
            <div class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center">
                    <i data-lucide="utensils" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white leading-none">QB Restaurant</h1>
                    <p class="text-xs text-gray-400 mt-0.5">Management System</p>
                </div>
            </div>

            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-1">Welcome back</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Sign in to your account to continue</p>

            <?php if (!empty($errors)): ?>
            <div class="flex gap-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg mb-5 text-sm">
                <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 shrink-0"></i>
                <div><?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?></div>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="flex gap-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-lg mb-5 text-sm">
                <i data-lucide="check-circle" class="w-4 h-4 mt-0.5 shrink-0"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="login-handler.php" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email address</label>
                    <div class="relative">
                        <i data-lucide="mail" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                        <input type="email" name="email" placeholder="you@example.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               class="input-field pl-10" required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                        <input type="password" name="password" id="passwordInput" placeholder="••••••••"
                               class="input-field pl-10 pr-10" required>
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i data-lucide="eye" id="eyeIcon" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors text-sm flex items-center justify-center gap-2 mt-2">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    Sign In
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">
                Don't have an account?
                <a href="register.php" class="text-indigo-600 hover:text-indigo-700 font-medium">Register restaurant</a>
            </p>

            <!-- Demo credentials -->
            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Demo Credentials</p>
                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 mb-1">
                    <i data-lucide="mail" class="w-3 h-3 text-gray-400"></i> admin@demo.com
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                    <i data-lucide="key" class="w-3 h-3 text-gray-400"></i> password123
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function toggleTheme() {
            const html = document.documentElement;
            html.classList.toggle('dark');
            localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
        }

        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        // Apply saved theme
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>
</html>
