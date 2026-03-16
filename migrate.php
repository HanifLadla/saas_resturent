<?php

// Database connection
$host = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'qb_restaurant_system';

try {
    // Create database if not exists
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database");
    
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database created successfully\n";
    
    // Run migrations
    $migrations = [
        // Core system tables
        "CREATE TABLE IF NOT EXISTS restaurants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE,
            phone VARCHAR(20),
            address TEXT,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            subscription_status ENUM('trial', 'active', 'expired', 'cancelled') DEFAULT 'trial',
            subscription_expires_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            restaurant_id INT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('super_admin', 'restaurant_admin', 'branch_manager', 'cashier', 'kitchen_staff', 'accountant') DEFAULT 'cashier',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS branches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            restaurant_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            address TEXT,
            phone VARCHAR(20),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
        )",
        
        // Product and inventory tables
        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            restaurant_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            restaurant_id INT NOT NULL,
            category_id INT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            cost_price DECIMAL(10,2),
            sku VARCHAR(100),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        )",
        
        // Orders and sales tables
        "CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            restaurant_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255),
            phone VARCHAR(20),
            address TEXT,
            loyalty_points INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            restaurant_id INT NOT NULL,
            branch_id INT,
            customer_id INT,
            user_id INT,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            type ENUM('dine_in', 'takeaway', 'delivery') DEFAULT 'dine_in',
            table_number VARCHAR(20),
            status ENUM('pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'pending',
            subtotal DECIMAL(10,2) NOT NULL,
            tax_amount DECIMAL(10,2) DEFAULT 0,
            discount_amount DECIMAL(10,2) DEFAULT 0,
            total_amount DECIMAL(10,2) NOT NULL,
            payment_status ENUM('pending', 'paid', 'partial', 'refunded') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
            FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )",
        
        "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )",
        
        // Global settings table
        "CREATE TABLE IF NOT EXISTS global_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            restaurant_id INT NOT NULL,
            category VARCHAR(100) NOT NULL,
            setting_key VARCHAR(100) NOT NULL,
            setting_value TEXT,
            data_type ENUM('string', 'integer', 'boolean', 'decimal', 'json') DEFAULT 'string',
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
            UNIQUE KEY unique_setting (restaurant_id, category, setting_key)
        )"
    ];
    
    foreach ($migrations as $migration) {
        $pdo->exec($migration);
        echo "Migration executed successfully\n";
    }
    
    // Insert sample data
    $seeders = [
        // Sample restaurant
        "INSERT IGNORE INTO restaurants (id, name, email, phone, address, status, subscription_status, subscription_expires_at) 
         VALUES (1, 'Demo Restaurant', 'demo@restaurant.com', '+1234567890', '123 Main St, City', 'active', 'trial', DATE_ADD(NOW(), INTERVAL 30 DAY))",
        
        // Sample admin user
        "INSERT IGNORE INTO users (id, restaurant_id, name, email, password, role, status) 
         VALUES (1, 1, 'Admin User', 'admin@demo.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'restaurant_admin', 'active')",
        
        // Sample branch
        "INSERT IGNORE INTO branches (id, restaurant_id, name, address, phone, status) 
         VALUES (1, 1, 'Main Branch', '123 Main St, City', '+1234567890', 'active')",
        
        // Sample categories
        "INSERT IGNORE INTO categories (id, restaurant_id, name, description, status) VALUES 
         (1, 1, 'Appetizers', 'Starter dishes', 'active'),
         (2, 1, 'Main Course', 'Main dishes', 'active'),
         (3, 1, 'Beverages', 'Drinks and beverages', 'active'),
         (4, 1, 'Desserts', 'Sweet dishes', 'active')",
        
        // Sample products
        "INSERT IGNORE INTO products (id, restaurant_id, category_id, name, description, price, cost_price, sku, status) VALUES 
         (1, 1, 1, 'Caesar Salad', 'Fresh romaine lettuce with caesar dressing', 12.99, 6.50, 'APP001', 'active'),
         (2, 1, 2, 'Grilled Chicken', 'Grilled chicken breast with vegetables', 18.99, 9.50, 'MAIN001', 'active'),
         (3, 1, 3, 'Fresh Orange Juice', 'Freshly squeezed orange juice', 4.99, 2.00, 'BEV001', 'active'),
         (4, 1, 4, 'Chocolate Cake', 'Rich chocolate cake slice', 6.99, 3.50, 'DES001', 'active')",
        
        // Sample customer
        "INSERT IGNORE INTO customers (id, restaurant_id, name, email, phone, address, loyalty_points) 
         VALUES (1, 1, 'John Doe', 'john@example.com', '+1234567891', '456 Oak St, City', 100)",
        
        // Global settings
        "INSERT IGNORE INTO global_settings (restaurant_id, category, setting_key, setting_value, data_type, description) VALUES 
         (1, 'general', 'restaurant_name', 'Demo Restaurant', 'string', 'Restaurant display name'),
         (1, 'general', 'currency', 'Rs', 'string', 'Default currency'),
         (1, 'general', 'timezone', 'UTC', 'string', 'Restaurant timezone'),
         (1, 'pos', 'auto_print_receipt', 'true', 'boolean', 'Auto print receipt after order'),
         (1, 'pos', 'allow_discount', 'true', 'boolean', 'Allow discounts on orders'),
         (1, 'kitchen', 'auto_refresh_seconds', '30', 'integer', 'Kitchen display refresh interval'),
         (1, 'tax', 'default_tax_rate', '10.00', 'decimal', 'Default tax rate percentage')"
    ];
    
    foreach ($seeders as $seeder) {
        $pdo->exec($seeder);
        echo "Seeder executed successfully\n";
    }
    
    echo "\nDatabase migration and seeding completed successfully!\n";
    echo "Demo login credentials:\n";
    echo "Email: admin@demo.com\n";
    echo "Password: password123\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}