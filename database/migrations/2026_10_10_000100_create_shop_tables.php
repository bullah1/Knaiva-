<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->after('password'); // admin | staff | user
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('image_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->text('description_bn')->nullable();
            $table->text('ingredients')->nullable();
            $table->text('nutrition')->nullable();
            $table->text('feeding_guide')->nullable();
            $table->json('features')->nullable();
            $table->json('images')->nullable();
            $table->json('tags')->nullable();
            $table->string('brand')->default('Kaniva');
            $table->string('sku')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('life_stage')->nullable();
            $table->string('food_type')->nullable();
            $table->string('flavor')->nullable();
            $table->decimal('rating', 2, 1)->default(0);
            $table->integer('review_count')->default(0);
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sold_count')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('weight_label');
            $table->string('sku')->nullable();
            $table->decimal('mrp', 10, 2)->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('discount_type')->default('percent'); // percent | fixed
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_order_value', 10, 2)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('phone');
            $table->text('address');
            $table->string('area')->nullable();
            $table->string('city')->nullable();
            $table->string('delivery_zone')->default('inside_dhaka');
            $table->string('payment_method')->default('cod');
            $table->string('coupon_code')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('status')->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('product_slug')->nullable();
            $table->string('variant_label')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('line_total', 10, 2);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('body')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('cover_url')->nullable();
            $table->string('author')->default('Kaniva Bangladesh');
            $table->string('category')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->string('og_image_url')->nullable();
            $table->timestamps();
        });

        Schema::create('hero_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->text('subtitle')->nullable();
            $table->text('subtitle_bn')->nullable();
            $table->string('image_url')->nullable();
            $table->string('cta_label')->nullable()->default('Shop Now');
            $table->string('cta_link')->nullable()->default('/shop');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('trust_points', function (Blueprint $table) {
            $table->id();
            $table->string('icon')->default('paw');
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->text('description')->nullable();
            $table->text('description_bn')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['site_settings', 'certificates', 'trust_points', 'hero_banners', 'blog_posts', 'reviews', 'order_items', 'orders', 'coupons', 'product_variants', 'products', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
    }
};
