<?php
session_start();
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — QB Restaurant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .input-field {
            width: 100%; padding: 0.625rem 1rem; border-radius: 0.5rem;
            border: 1px solid; font-size: 0.875rem; transition: all 0.15s;
            outline: none;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-950 flex items-center justify-center py-10 transition-colors duration-300">

    <button id="themeToggle" onclick="toggleTheme()"
        class="fixed top-4 right-4 p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all">
        <i data-lucide="sun" class="w-4 h-4 text-gray-600 dark:text-gray-300 hidden dark:block"></i>
        <i data-lucide="moon" class="w-4 h-4 text-gray-600 dark:text-gray-300 block dark:hidden"></i>
    </button>

    <div class="w-full max-w-xl px-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 p-8">

            <div class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center">
                    <i data-lucide="utensils" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white leading-none">QB Restaurant</h1>
                    <p class="text-xs text-gray-400 mt-0.5">Management System</p>
                </div>
            </div>

            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-1">Register your restaurant</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Start your 30-day free trial — no credit card required</p>

            <?php if (!empty($errors)): ?>
            <div class="flex gap-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg mb-5 text-sm">
                <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 shrink-0"></i>
                <div><?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?></div>
            </div>
            <?php endif; ?>

            <form method="POST" action="register-handler.php" class="space-y-4">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Restaurant Name</label>
                        <div class="relative">
                            <i data-lucide="store" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                            <input type="text" name="restaurant_name" placeholder="My Restaurant"
                                   value="<?php echo htmlspecialchars($old['restaurant_name'] ?? ''); ?>"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm transition" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Owner Name</label>
                        <div class="relative">
                            <i data-lucide="user" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                            <input type="text" name="owner_name" placeholder="John Doe"
                                   value="<?php echo htmlspecialchars($old['owner_name'] ?? ''); ?>"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm transition" required>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email Address</label>
                        <div class="relative">
                            <i data-lucide="mail" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                            <input type="email" name="email" placeholder="you@example.com"
                                   value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm transition" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Phone Number</label>
                        <div class="relative">
                            <i data-lucide="phone" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                            <input type="text" name="phone" placeholder="+92 300 0000000"
                                   value="<?php echo htmlspecialchars($old['phone'] ?? ''); ?>"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm transition" required>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Address</label>
                    <div class="relative">
                        <i data-lucide="map-pin" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                        <textarea name="address" rows="2" placeholder="123 Main Street, City"
                                  class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm transition resize-none" required><?php echo htmlspecialchars($old['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                            <input type="password" name="password" placeholder="Min. 8 characters"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm transition" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Confirm Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                            <input type="password" name="password_confirmation" placeholder="Repeat password"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm transition" required>
                        </div>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors text-sm flex items-center justify-center gap-2 mt-2">
                    <i data-lucide="building-2" class="w-4 h-4"></i>
                    Create Restaurant Account
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">
                Already have an account?
                <a href="login.php" class="text-indigo-600 hover:text-indigo-700 font-medium">Sign in</a>
            </p>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function toggleTheme() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        }
        if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
    </script>
</body>
</html>
