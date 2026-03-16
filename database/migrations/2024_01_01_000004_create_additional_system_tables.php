<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Notifications System
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['email', 'sms', 'whatsapp']);
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->text('message');
            $table->enum('status', ['pending', 'sent', 'failed']);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['email', 'sms', 'whatsapp']);
            $table->string('event');
            $table->string('subject')->nullable();
            $table->text('content');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // System Logs & Audit
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        // API Management
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key')->unique();
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');
            $table->string('method');
            $table->integer('response_code');
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        // Stock Adjustments
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('old_quantity', 10, 3);
            $table->decimal('new_quantity', 10, 3);
            $table->decimal('adjustment', 10, 3);
            $table->string('reason');
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });

        // Loyalty Transactions
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->integer('points');
            $table->enum('type', ['earned', 'redeemed']);
            $table->string('reason');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });

        // Scheduled Reports
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('report_type');
            $table->enum('frequency', ['daily', 'weekly', 'monthly']);
            $table->enum('format', ['pdf', 'excel']);
            $table->json('email_recipients');
            $table->json('parameters')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('api_logs');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notifications');
    }
};