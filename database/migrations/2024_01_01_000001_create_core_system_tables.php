<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Core System Tables
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->unique();
            $table->string('phone');
            $table->text('address');
            $table->string('logo')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('timezone')->default('UTC');
            $table->json('settings')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('subscription_expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('phone');
            $table->text('address');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->enum('role', ['super_admin', 'restaurant_admin', 'branch_manager', 'cashier', 'kitchen_staff', 'accountant']);
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        // Subscription & Billing
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->enum('billing_cycle', ['monthly', 'yearly', 'lifetime', 'commission']);
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->json('features');
            $table->integer('max_branches')->default(1);
            $table->integer('max_users')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled', 'suspended']);
            $table->decimal('amount_paid', 10, 2);
            $table->json('features_snapshot');
            $table->timestamps();
        });

        // Global Settings
        Schema::create('global_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // appearance, pos, printer, tax, etc.
            $table->string('key');
            $table->text('value');
            $table->string('type')->default('string'); // string, json, boolean, integer
            $table->timestamps();
            
            $table->unique(['restaurant_id', 'category', 'key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('global_settings');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('users');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('restaurants');
    }
};