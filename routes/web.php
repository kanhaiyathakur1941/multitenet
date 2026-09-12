<?php

use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\CartController;
use App\Http\Controllers\Portal\CheckoutController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\EventController;
use App\Http\Controllers\Portal\EventRegistrationController;
use App\Http\Controllers\Portal\OrderController;
use App\Http\Controllers\Portal\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/', [AuthController::class, 'create'])->name('portal.login');
    Route::post('/login', [AuthController::class, 'store'])->name('portal.login.store');
});

Route::redirect('/portal/login', '/');

Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome');

Route::prefix('portal')->name('portal.')->group(function (): void {
    Route::middleware(['auth', 'customer', 'tenant'])->group(function (): void {
        Route::post('logout', [AuthController::class, 'destroy'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('events', [EventController::class, 'index'])->name('events.index');
        Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
        Route::post('events/{event}/register', [EventRegistrationController::class, 'store'])
            ->name('events.register');

        Route::get('products', [ProductController::class, 'index'])->name('products.index');

        Route::get('cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('cart/products/{product}', [CartController::class, 'store'])->name('cart.store');
        Route::patch('cart/products/{product}', [CartController::class, 'update'])->name('cart.update');
        Route::post('cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');

        Route::get('checkout/{order}', [CheckoutController::class, 'show'])->name('checkout.show');
        Route::post('checkout/{order}/verify', [CheckoutController::class, 'verify'])->name('checkout.verify');
        Route::get('checkout/{order}/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    });
});
