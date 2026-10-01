<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('countries');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('min_subtotal')->nullable();
            $table->unsignedInteger('max_subtotal')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('phone')->nullable();
            // open, cancelled, archived
            $table->string('status', 20)->default('open')->index();
            // pending, paid, partially_refunded, refunded, failed, expired, cancelled
            $table->string('financial_status', 30)->default('pending')->index();
            // unfulfilled, partially_fulfilled, fulfilled
            $table->string('fulfillment_status', 30)->default('unfulfilled')->index();
            $table->char('currency', 3)->default('EUR');
            $table->integer('subtotal');
            $table->integer('discount_total')->default(0);
            $table->integer('shipping_total')->default(0);
            $table->integer('tax_total')->default(0);
            $table->integer('gift_card_used')->default(0);
            $table->integer('credit_used')->default(0);
            $table->integer('total');
            $table->integer('refunded_total')->default(0);
            $table->string('discount_code')->nullable();
            $table->foreignId('discount_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gift_card_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shipping_method')->nullable();
            $table->foreignId('shipping_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->text('customer_note')->nullable();
            $table->text('note')->nullable();
            $table->json('tags')->nullable();
            $table->integer('points_earned')->default(0);
            $table->timestamp('points_awarded_at')->nullable();
            $table->boolean('accepts_marketing')->default(false);
            $table->string('source', 20)->default('web');
            $table->string('token', 64)->unique();
            $table->string('ip', 45)->nullable();
            $table->timestamp('placed_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->unsignedBigInteger('shopify_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('title');
            $table->string('variant_title')->nullable();
            $table->string('sku')->nullable();
            $table->string('image')->nullable();
            $table->integer('price');
            $table->unsignedInteger('quantity');
            $table->decimal('tax_rate', 5, 2)->default(9);
            $table->integer('discount_allocated')->default(0);
            $table->integer('total');
            $table->unsignedInteger('fulfilled_quantity')->default(0);
            $table->unsignedInteger('refunded_quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->text('message');
            $table->json('data')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20);
            $table->string('provider_id')->nullable()->index();
            $table->string('method')->nullable();
            $table->integer('amount');
            $table->string('status', 30);
            $table->timestamp('paid_at')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('amount');
            $table->string('reason')->nullable();
            $table->string('provider_id')->nullable();
            $table->string('status', 30);
            $table->boolean('restock')->default(false);
            $table->json('items')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('tracking_company')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->json('items')->nullable();
            $table->boolean('notify_customer')->default(true);
            $table->timestamp('shipped_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('gift_card_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('amount');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // Verlaten winkelwagens: aangemaakt zodra iemand de checkout start
        Schema::create('checkouts', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable()->index();
            $table->json('cart');
            $table->integer('subtotal')->default(0);
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkouts');
        Schema::dropIfExists('gift_card_transactions');
        Schema::dropIfExists('fulfillments');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_zones');
    }
};
