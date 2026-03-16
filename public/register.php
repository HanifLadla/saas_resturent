<?php
session_start();
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Restaurant - QB Restaurant System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12">
        <div class="max-w-lg w-full bg-white rounded-lg shadow-md p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">QB Restaurant</h1>
                <p class="text-gray-600 mt-2">Register your restaurant</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php foreach ($errors as $error): ?>
                        <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register-handler.php">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Restaurant Name</label>
                        <input type="text" name="restaurant_name" value="<?php echo htmlspecialchars($old['restaurant_name'] ?? ''); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Owner Name</label>
                        <input type="text" name="owner_name" value="<?php echo htmlspecialchars($old['owner_name'] ?? ''); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($old['phone'] ?? ''); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Address</label>
                    <textarea name="address" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" required><?php echo htmlspecialchars($old['address'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                        <input type="password" name="password" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" required>
                        <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Confirm Password</label>
                        <input type="password" name="password_confirmation" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" required>
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
                    Register Restaurant
                </button>
            </form>

            <div class="text-center mt-6">
                <p class="text-gray-600">Already have an account? 
                    <a href="login.php" class="text-blue-600 hover:text-blue-800">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>