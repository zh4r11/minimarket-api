<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\StockMovementController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\UnitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Routes for API version 1.
|
*/

// Public routes with auth rate limiter (5/min - brute force protection)
Route::middleware('throttle:auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->name('api.v1.register');
    Route::post('login', [AuthController::class, 'login'])->name('api.v1.login');
});

// Protected routes with authenticated rate limiter (120/min)
Route::middleware(['auth:sanctum', 'throttle:authenticated'])->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.logout');
    Route::get('me', [AuthController::class, 'me'])->name('api.v1.me');

    // Minimarket resources
    Route::apiResource('categories', CategoryController::class)->names([
        'index' => 'api.v1.categories.index',
        'store' => 'api.v1.categories.store',
        'show' => 'api.v1.categories.show',
        'update' => 'api.v1.categories.update',
        'destroy' => 'api.v1.categories.destroy',
    ]);
    Route::apiResource('units', UnitController::class)->names([
        'index' => 'api.v1.units.index',
        'store' => 'api.v1.units.store',
        'show' => 'api.v1.units.show',
        'update' => 'api.v1.units.update',
        'destroy' => 'api.v1.units.destroy',
    ]);
    Route::apiResource('suppliers', SupplierController::class)->names([
        'index' => 'api.v1.suppliers.index',
        'store' => 'api.v1.suppliers.store',
        'show' => 'api.v1.suppliers.show',
        'update' => 'api.v1.suppliers.update',
        'destroy' => 'api.v1.suppliers.destroy',
    ]);
    Route::apiResource('products', ProductController::class)->names([
        'index' => 'api.v1.products.index',
        'store' => 'api.v1.products.store',
        'show' => 'api.v1.products.show',
        'update' => 'api.v1.products.update',
        'destroy' => 'api.v1.products.destroy',
    ]);
    Route::apiResource('purchases', PurchaseController::class)->names([
        'index' => 'api.v1.purchases.index',
        'store' => 'api.v1.purchases.store',
        'show' => 'api.v1.purchases.show',
        'update' => 'api.v1.purchases.update',
        'destroy' => 'api.v1.purchases.destroy',
    ]);
    Route::apiResource('sales', SaleController::class)->names([
        'index' => 'api.v1.sales.index',
        'store' => 'api.v1.sales.store',
        'show' => 'api.v1.sales.show',
        'update' => 'api.v1.sales.update',
        'destroy' => 'api.v1.sales.destroy',
    ]);
    Route::get('stock-movements', [StockMovementController::class, 'index'])->name('api.v1.stock-movements.index');
    Route::post('stock-movements', [StockMovementController::class, 'store'])->name('api.v1.stock-movements.store');
    Route::get('stock-movements/{stockMovement}', [StockMovementController::class, 'show'])->name('api.v1.stock-movements.show');

    // Email verification
    Route::post('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('email/resend', [AuthController::class, 'resendVerificationEmail'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// Password reset routes (public with rate limiting)
Route::middleware('throttle:6,1')->group(function (): void {
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
        ->name('password.email');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])
        ->name('password.reset');
});
