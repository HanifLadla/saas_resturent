<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Menu & Products
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('sku')->unique();
            $table->decimal('price', 10, 2);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->string('image')->nullable();
            $table->json('variants')->nullable(); // sizes, extras, etc.
            $table->boolean('track_inventory')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_alert')->default(5);
            $table->json('kitchen_stations')->nullable(); // grill, fry, bar, etc.
            $table->integer('preparation_time')->default(15); // minutes
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // POS & Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); // cashier
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number')->unique();
            $table->enum('type', ['dine_in', 'takeaway', 'delivery', 'online']);
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'ready', 'served', 'completed', 'cancelled']);
            $table->string('table_number')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->json('tax_breakdown')->nullable();
            $table->json('discount_details')->nullable();
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'refunded']);
            $table->text('notes')->nullable();
            $table->timestamp('estimated_ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->string('product_name'); // snapshot
            $table->decimal('unit_price', 10, 2);
            $table->integer('quantity');
            $table->json('variants')->nullable(); // selected variants
            $table->json('modifiers')->nullable(); // extras, customizations
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['pending', 'preparing', 'ready', 'served']);
            $table->text('special_instructions')->nullable();
            $table->timestamps();
        });

        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('method', ['cash', 'card', 'digital_wallet', 'bank_transfer', 'credit']);
            $table->decimal('amount', 10, 2);
            $table->string('reference_number')->nullable();
            $table->json('gateway_response')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded']);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        // Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->integer('loyalty_points')->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->integer('visit_count')->default(0);
            $table->timestamp('last_visit_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};