<?php

use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Shop\AccountController;
use App\Http\Controllers\Shop\AuthController;
use App\Http\Controllers\Shop\BlogController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\CollectionController;
use App\Http\Controllers\Shop\ContactController;
use App\Http\Controllers\Shop\FeedController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\MediaController;
use App\Http\Controllers\Shop\NewsletterController;
use App\Http\Controllers\Shop\PageController;
use App\Http\Controllers\Shop\PaymentController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\SearchController;
use App\Http\Middleware\TrackVisit;
use Illuminate\Support\Facades\Route;

// Webadressen zijn gelijk aan die van Shopify, zodat links en Google-posities blijven werken.

Route::get('/media/{width}/{path}', MediaController::class)->where(['width' => '[0-9]+', 'path' => '.*'])->name('media');

Route::post('/webhooks/mollie', [PaymentController::class, 'webhook'])->name('webhooks.mollie');

Route::get('/feeds/meta-catalog.xml', [FeedController::class, 'metaCatalog'])->name('feeds.meta');
Route::get('/sitemap.xml', [FeedController::class, 'sitemap']);
Route::get('/robots.txt', [FeedController::class, 'robots']);

Route::middleware(TrackVisit::class)->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');
    Route::get('/collections/{handle}', [CollectionController::class, 'show'])->name('collections.show');
    Route::get('/products/{handle}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{handle}/quick-view', [ProductController::class, 'quickView'])->name('products.quick');
    Route::get('/pages/{handle}', [PageController::class, 'show'])->name('pages.show');
    Route::get('/blogs/{blog}', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blogs/{blog}/tagged/{tag}', [BlogController::class, 'index'])->name('blog.tagged');
    Route::get('/blogs/{blog}/{handle}', [BlogController::class, 'show'])->name('blog.show');
    Route::get('/search', SearchController::class)->name('search');

    Route::get('/cart', [CartController::class, 'show'])->name('cart');
    Route::get('/cart/recover/{token}', [CartController::class, 'recover'])->name('cart.recover');
    Route::get('/discount/{code}', [CartController::class, 'discount'])->name('discount.link');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::get('/orders/{token}', [CheckoutController::class, 'status'])->name('orders.status');

    Route::prefix('account')->name('account.')->group(function () {
        Route::middleware('guest:customer')->group(function () {
            Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
            Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
            Route::get('/recover', [AuthController::class, 'showRecover'])->name('recover');
            Route::get('/reset/{token}', [AuthController::class, 'showReset'])->name('reset');
        });
        Route::middleware('auth:customer')->group(function () {
            Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
            Route::get('/orders/{number}', [AccountController::class, 'order'])->name('order');
            Route::get('/addresses', [AccountController::class, 'addresses'])->name('addresses');
        });
    });
});

Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/change', [CartController::class, 'change'])->name('cart.change');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::get('/cart/drawer', [CartController::class, 'drawer'])->name('cart.drawer');

Route::post('/checkout/quote', [CheckoutController::class, 'quote'])->name('checkout.quote');
Route::post('/checkout', [CheckoutController::class, 'place'])->middleware('throttle:10,1')->name('checkout.place');
Route::get('/checkout/return/{token}', [CheckoutController::class, 'return'])->name('checkout.return');

Route::get('/payments/test/{payment}', [PaymentController::class, 'test'])->name('payments.test');
Route::post('/payments/test/{payment}', [PaymentController::class, 'completeTest'])->name('payments.test.complete');

Route::post('/newsletter', [NewsletterController::class, 'subscribe'])->middleware('throttle:10,1')->name('newsletter');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:6,1')->name('contact');

Route::prefix('account')->name('account.')->group(function () {
    Route::middleware(['guest:customer', 'throttle:10,1'])->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/recover', [AuthController::class, 'recover']);
        Route::post('/reset', [AuthController::class, 'reset'])->name('reset.update');
    });
    Route::middleware('auth:customer')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/loyalty/redeem', [AccountController::class, 'redeem'])->name('redeem');
        Route::post('/addresses', [AccountController::class, 'storeAddress'])->name('addresses.store');
        Route::put('/addresses/{address}', [AccountController::class, 'updateAddress'])->name('addresses.update');
        Route::delete('/addresses/{address}', [AccountController::class, 'deleteAddress'])->name('addresses.delete');
    });
});

// Documenten voor het beheer (factuur en pakbon)
Route::middleware('auth:web')->prefix('beheer/documenten')->name('beheer.')->group(function () {
    Route::get('/factuur/{order}', [DocumentController::class, 'invoice'])->name('invoice');
    Route::get('/pakbon/{order}', [DocumentController::class, 'packingSlip'])->name('packing-slip');
});
