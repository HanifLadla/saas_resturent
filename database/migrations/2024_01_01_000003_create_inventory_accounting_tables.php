<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Inventory Management
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->text('address');
            $table->string('contact_person')->nullable();
            $table->decimal('credit_limit', 10, 2)->default(0);
            $table->integer('payment_terms')->default(30); // days
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->unique();
            $table->text('description')->nullable();
            $table->string('unit'); // kg, liter, piece, etc.
            $table->decimal('current_stock', 10, 3)->default(0);
            $table->decimal('minimum_stock', 10, 3)->default(0);
            $table->decimal('maximum_stock', 10, 3)->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained();
            $table->string('po_number')->unique();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->enum('status', ['draft', 'sent', 'confirmed', 'received', 'cancelled']);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained();
            $table->decimal('quantity_ordered', 10, 3);
            $table->decimal('quantity_received', 10, 3)->default(0);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
        });

        // Accounting System
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('account_code')->unique();
            $table->string('account_name');
            $table->enum('account_type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->enum('account_subtype', [
                'current_asset', 'fixed_asset', 'current_liability', 'long_term_liability',
                'owner_equity', 'sales_revenue', 'other_revenue', 'cost_of_goods_sold',
                'operating_expense', 'other_expense'
            ]);
            $table->foreignId('parent_account_id')->nullable()->constrained('chart_of_accounts');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entry_number')->unique();
            $table->date('entry_date');
            $table->string('reference_type')->nullable(); // order, purchase, payment, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description');
            $table->decimal('total_debit', 15, 2);
            $table->decimal('total_credit', 15, 2);
            $table->enum('status', ['draft', 'posted', 'reversed']);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts');
            $table->text('description');
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        // Kitchen Display System
        Schema::create('kitchen_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Grill, Fry, Bar, Dessert, etc.
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->json('assigned_categories')->nullable(); // product categories
            $table->json('display_settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('kitchen_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kitchen_station_id')->constrained();
            $table->json('items'); // order items for this station
            $table->enum('status', ['pending', 'preparing', 'ready']);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('estimated_time')->nullable(); // minutes
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kitchen_orders');
        Schema::dropIfExists('kitchen_stations');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('suppliers');
    }
};