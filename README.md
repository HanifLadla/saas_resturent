# QB Modern Restaurant System

## 🏪 Enterprise-Grade Multi-Restaurant Management Platform

A comprehensive, scalable Restaurant Management System built with Laravel, featuring POS, Kitchen Display, Inventory Management, Accounting, and Multi-Tenant Architecture.

## 🚀 Key Features

### 🏗️ **Multi-Restaurant & Multi-Branch Architecture**
- Unlimited restaurants with multiple branches
- Strong data isolation per restaurant
- Role-based access control (Super Admin, Restaurant Admin, Branch Manager, Cashier, Kitchen Staff, Accountant)

### 💳 **Subscription & Billing Engine**
- Multiple subscription models (Monthly, Yearly, Lifetime, Commission-based)
- Feature-based access control
- Auto expiry & grace periods
- Usage-based billing calculations

### 🍽️ **Point of Sale (POS) System**
- Real-time order processing
- Multiple payment methods
- Customer management & loyalty
- Receipt printing & customization
- Order modifications & discounts

### 🖥️ **Kitchen Display System (KDS)**
- Real-time order updates via WebSockets
- Color-coded order statuses & priorities
- Station-wise order distribution
- Preparation time tracking
- Sound & visual alerts

### 📊 **Inventory & Stock Management**
- Real-time stock tracking
- Low stock alerts
- Purchase order management
- Supplier management
- Stock adjustments & reporting

### 📒 **Professional Accounting Module**
- Chart of Accounts (fully dynamic)
- Double-entry bookkeeping
- Journal entries & auto-posting
- Trial Balance, Balance Sheet, P&L
- Multi-ledger support
- Fiscal year handling

### ⚙️ **Global Settings System**
- Centralized configuration management
- Database-driven settings
- Cached for performance
- Category-based organization
- Real-time application updates

## 🏗️ **System Architecture**

```
QB Modern Restaurant System/
├── app/
│   ├── Models/           # Core Eloquent models
│   ├── Services/         # Business logic services
│   └── Http/Controllers/ # Base controllers
├── Modules/              # Modular architecture
│   ├── POS/
│   │   ├── Controllers/
│   │   ├── Services/
│   │   ├── Models/
│   │   └── Views/
│   ├── Dashboard/
│   ├── Inventory/
│   ├── Accounting/
│   └── KitchenDisplay/
├── database/
│   └── migrations/       # Database schema
└── resources/
    └── views/            # Blade templates
```

## 📊 **Database Schema Overview**

### Core Tables
- `restaurants` - Multi-tenant restaurant data
- `branches` - Restaurant branch management
- `users` - Staff & user management
- `subscriptions` - Billing & feature control
- `global_settings` - System configuration

### POS & Sales
- `orders` - Order management
- `order_items` - Order line items
- `payments` - Payment processing
- `customers` - Customer data & loyalty

### Inventory
- `products` - Menu items & inventory
- `categories` - Product categorization
- `inventory_items` - Stock management
- `purchase_orders` - Procurement

### Accounting
- `chart_of_accounts` - Account structure
- `journal_entries` - Financial transactions
- `journal_entry_lines` - Transaction details

### Kitchen Operations
- `kitchen_stations` - Kitchen workflow
- `kitchen_orders` - Real-time order tracking

## 🛠️ **Technology Stack**

- **Backend:** Laravel 10+ (PHP 8.1+)
- **Database:** MySQL 8.0+
- **Frontend:** Blade Templates + Alpine.js + Tailwind CSS
- **Real-time:** Laravel WebSockets + Pusher
- **Caching:** Redis
- **Queue:** Redis
- **Authentication:** Laravel Sanctum
- **Permissions:** Spatie Laravel Permission

## 📦 **Installation & Setup**

### Prerequisites
- PHP 8.1 or higher
- MySQL 8.0 or higher
- Redis server
- Composer
- Node.js & NPM

### Installation Steps

1. **Clone & Install Dependencies**
```bash
git clone <repository-url> qb-restaurant-system
cd qb-restaurant-system
composer install
npm install
```

2. **Environment Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Database Setup**
```bash
# Configure database in .env file
DB_DATABASE=qb_restaurant_system
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations
php artisan migrate
php artisan db:seed
```

4. **Redis Configuration**
```bash
# Configure Redis in .env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

5. **WebSocket Setup**
```bash
# Install Laravel WebSockets
php artisan websockets:serve

# Configure broadcasting
BROADCAST_DRIVER=pusher
```

6. **Start Development Server**
```bash
php artisan serve
npm run dev

# Start queue worker
php artisan queue:work

# Start WebSocket server
php artisan websockets:serve
```

## 🔧 **Configuration**

### Global Settings Categories

1. **Appearance Settings**
   - Theme & branding
   - Colors & logos
   - UI customization

2. **POS Settings**
   - Order flow configuration
   - Payment methods
   - Discount policies

3. **Kitchen Display Settings**
   - Screen layouts
   - Refresh intervals
   - Alert configurations

4. **Tax Settings**
   - Multi-tax support
   - Inclusive/Exclusive pricing
   - Tax reporting

5. **Accounting Settings**
   - Fiscal year configuration
   - Default accounts
   - Auto-posting rules

## 🚀 **Usage Examples**

### Creating an Order (POS)
```php
$order = $posService->createOrder([
    'type' => 'dine_in',
    'table_number' => '5',
    'items' => [
        [
            'product_id' => 1,
            'quantity' => 2,
            'variants' => [['name' => 'Size', 'value' => 'Large']],
            'modifiers' => [['name' => 'Extra Cheese', 'price' => 2.00]]
        ]
    ]
]);
```

### Kitchen Display Integration
```php
// Send order to kitchen
$kdsService->sendToKitchen($order);

// Update order status
$kdsService->updateKitchenOrderStatus($kitchenOrder, 'preparing');
```

### Global Settings Management
```php
// Get setting
$autoRefresh = $settingsService->get($restaurantId, 'kitchen_display', 'auto_refresh_seconds', 30);

// Set setting
$settingsService->set($restaurantId, 'pos', 'allow_discount', true, 'boolean');
```

## 🔐 **Security Features**

- Multi-tenant data isolation
- Role-based permissions
- API rate limiting
- Secure payment processing
- Audit logging
- Data encryption

## 📈 **Scalability Features**

- Modular architecture
- Database optimization
- Redis caching
- Queue processing
- WebSocket real-time updates
- API-first design

## 🎨 **UI/UX Features**

- Modern, responsive design
- Dark/Light mode support
- Real-time updates
- Smooth animations
- Mobile-optimized
- Accessibility compliant

## 📊 **Reporting & Analytics**

- Sales reports
- Inventory reports
- Financial statements
- Performance analytics
- Custom report builder
- Export capabilities (PDF, Excel, CSV)

## 🔧 **API Documentation**

The system provides RESTful APIs for all major operations:

- `/api/v1/orders` - Order management
- `/api/v1/products` - Product catalog
- `/api/v1/customers` - Customer data
- `/api/v1/inventory` - Stock management
- `/api/v1/kitchen/orders` - Kitchen operations

## 🤝 **Contributing**

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📄 **License**

This project is proprietary software. All rights reserved.

## 📞 **Support**

For technical support and inquiries:
- Email: support@qbrestaurant.com
- Documentation: [docs.qbrestaurant.com](https://docs.qbrestaurant.com)
- Support Portal: [support.qbrestaurant.com](https://support.qbrestaurant.com)

---

**QB Modern Restaurant System** - Empowering restaurants with enterprise-grade technology.