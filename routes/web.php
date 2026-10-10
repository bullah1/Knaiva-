<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

// Storefront
Route::get('/', HomeController::class)->name('home');
Route::get('/shop', ShopController::class)->name('shop');
Route::get('/category/{slug}', ShopController::class)->name('category');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/returns', [PageController::class, 'returns'])->name('returns');
Route::get('/lang/{lang}', [PageController::class, 'setLang'])->whereIn('lang', ['en', 'bn'])->name('lang');

// Cart & checkout
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/coupon', [CartController::class, 'coupon'])->name('cart.coupon');
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/order/{orderNumber}', [OrderController::class, 'show'])->name('order.show');
Route::post('/order/{orderNumber}', [OrderController::class, 'verify'])->middleware('throttle:10,1')->name('order.verify');

// SEO
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots']);

// Staff auth
Route::middleware('guest')->group(function () {
    Route::get('/auth', [AuthController::class, 'show'])->name('login');
    Route::post('/auth', [AuthController::class, 'login'])->middleware('throttle:6,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Admin panel
Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::resource('products', Admin\ProductController::class)->except('show');
    Route::post('products/{product}/toggle', [Admin\ProductController::class, 'toggle'])->name('products.toggle');
    Route::delete('products/{product}/images', [Admin\ProductController::class, 'removeImage'])->name('products.images.remove');

    Route::resource('categories', Admin\CategoryController::class)->except('show');

    Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [Admin\OrderController::class, 'status'])->name('orders.status');

    Route::get('coupons', [Admin\CouponController::class, 'index'])->name('coupons.index');
    Route::post('coupons', [Admin\CouponController::class, 'store'])->name('coupons.store');
    Route::post('coupons/{coupon}/toggle', [Admin\CouponController::class, 'toggle'])->name('coupons.toggle');
    Route::delete('coupons/{coupon}', [Admin\CouponController::class, 'destroy'])->name('coupons.destroy');

    Route::resource('blog', Admin\BlogController::class)->except('show')->parameters(['blog' => 'post']);

    Route::get('reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::post('reviews', [Admin\ReviewController::class, 'store'])->name('reviews.store');
    Route::patch('reviews/{review}', [Admin\ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('reviews/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('homepage', [Admin\HomepageController::class, 'index'])->name('homepage');
    Route::post('homepage/{type}', [Admin\HomepageController::class, 'store'])->whereIn('type', ['banner', 'trust', 'certificate'])->name('homepage.store');
    Route::put('homepage/{type}/{id}', [Admin\HomepageController::class, 'update'])->whereIn('type', ['banner', 'trust', 'certificate'])->name('homepage.update');
    Route::delete('homepage/{type}/{id}', [Admin\HomepageController::class, 'destroy'])->whereIn('type', ['banner', 'trust', 'certificate'])->name('homepage.destroy');

    Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings');
    Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
    Route::put('account/password', [Admin\SettingsController::class, 'password'])->name('account.password');
});
