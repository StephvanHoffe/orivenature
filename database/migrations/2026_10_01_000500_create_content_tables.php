<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('handle')->unique();
            $table->longText('body')->nullable();
            $table->boolean('is_published')->default(true);
            // default, contact (contactformulier), wholesale (aanvraag retailer)
            $table->string('template', 30)->default('default');
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->default('contact');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('blog', 60)->default('news');
            $table->string('title');
            $table->string('handle');
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('image')->nullable();
            $table->string('author')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable()->index();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->unsignedBigInteger('shopify_id')->nullable()->index();
            $table->timestamps();
            $table->unique(['blog', 'handle']);
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('handle')->unique();
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('title');
            $table->string('url');
            $table->string('badge')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->unsignedInteger('hits')->default(0);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('visitor', 64);
            $table->string('landing_path')->nullable();
            $table->string('referrer')->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('device', 20)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unique(['date', 'visitor']);
        });

        Schema::create('storefront_events', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('visitor', 64)->nullable();
            // view_product, add_to_cart, begin_checkout, purchase
            $table->string('type', 30);
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('value')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['date', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_events');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('pages');
    }
};
