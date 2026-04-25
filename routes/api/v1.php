<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\BundleController;
use App\Http\Controllers\Api\V1\BundlePhotoController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductPhotoController;
use App\Http\Controllers\Api\V1\ProductVariantAttributeController;
use App\Http\Controllers\Api\V1\ProductVariantAttributeValueController;
use App\Http\Controllers\Api\V1\ProductVariantController;
use App\Http\Controllers\Api\V1\ProductVariantPhotoController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\StockMovementController;
use App\Http\Controllers\Api\V1\StoreSettingController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\UserController;
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
    Route::apiResource('brands', BrandController::class)->names([
        'index' => 'api.v1.brands.index',
        'store' => 'api.v1.brands.store',
        'show' => 'api.v1.brands.show',
        'update' => 'api.v1.brands.update',
        'destroy' => 'api.v1.brands.destroy',
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
    Route::apiResource('customers', CustomerController::class)->names([
        'index' => 'api.v1.customers.index',
        'store' => 'api.v1.customers.store',
        'show' => 'api.v1.customers.show',
        'update' => 'api.v1.customers.update',
        'destroy' => 'api.v1.customers.destroy',
    ]);
    Route::apiResource('products', ProductController::class)->names([
        'index' => 'api.v1.products.index',
        'store' => 'api.v1.products.store',
        'show' => 'api.v1.products.show',
        'update' => 'api.v1.products.update',
        'destroy' => 'api.v1.products.destroy',
    ]);
    Route::post('products/{product}/photos', [ProductPhotoController::class, 'store'])->name('api.v1.products.photos.store');
    Route::delete('products/{product}/photos/{photo}', [ProductPhotoController::class, 'destroy'])->name('api.v1.products.photos.destroy');
    Route::patch('products/{product}/photos/{photo}/main', [ProductPhotoController::class, 'setMain'])->name('api.v1.products.photos.set-main');
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
    Route::post('stock-adjustments', [StockMovementController::class, 'adjust'])->name('api.v1.stock-adjustments.store');

    // Reports
    Route::get('reports/stock', [ReportController::class, 'stock'])->name('api.v1.reports.stock');

    // Product variants
    Route::apiResource('product-variant-attributes', ProductVariantAttributeController::class)->names([
        'index' => 'api.v1.product-variant-attributes.index',
        'store' => 'api.v1.product-variant-attributes.store',
        'show' => 'api.v1.product-variant-attributes.show',
        'update' => 'api.v1.product-variant-attributes.update',
        'destroy' => 'api.v1.product-variant-attributes.destroy',
    ]);
    Route::apiResource('product-variant-attribute-values', ProductVariantAttributeValueController::class)->names([
        'index' => 'api.v1.product-variant-attribute-values.index',
        'store' => 'api.v1.product-variant-attribute-values.store',
        'show' => 'api.v1.product-variant-attribute-values.show',
        'update' => 'api.v1.product-variant-attribute-values.update',
        'destroy' => 'api.v1.product-variant-attribute-values.destroy',
    ]);
    Route::apiResource('product-variants', ProductVariantController::class)->names([
        'index' => 'api.v1.product-variants.index',
        'store' => 'api.v1.product-variants.store',
        'show' => 'api.v1.product-variants.show',
        'update' => 'api.v1.product-variants.update',
        'destroy' => 'api.v1.product-variants.destroy',
    ]);
    Route::post('product-variants/{productVariant}/photos', [ProductVariantPhotoController::class, 'store'])->name('api.v1.product-variants.photos.store');
    Route::delete('product-variants/{productVariant}/photos/{photo}', [ProductVariantPhotoController::class, 'destroy'])->name('api.v1.product-variants.photos.destroy');
    Route::patch('product-variants/{productVariant}/photos/{photo}/main', [ProductVariantPhotoController::class, 'setMain'])->name('api.v1.product-variants.photos.set-main');

    // Bundles
    Route::apiResource('bundles', BundleController::class)->names([
        'index' => 'api.v1.bundles.index',
        'store' => 'api.v1.bundles.store',
        'show' => 'api.v1.bundles.show',
        'update' => 'api.v1.bundles.update',
        'destroy' => 'api.v1.bundles.destroy',
    ]);
    Route::post('bundles/{bundle}/photos', [BundlePhotoController::class, 'store'])->name('api.v1.bundles.photos.store');
    Route::delete('bundles/{bundle}/photos/{photo}', [BundlePhotoController::class, 'destroy'])->name('api.v1.bundles.photos.destroy');
    Route::patch('bundles/{bundle}/photos/{photo}/main', [BundlePhotoController::class, 'setMain'])->name('api.v1.bundles.photos.set-main');

    // User & role management (admin only)
    Route::middleware('role:admin')->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('api.v1.users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('api.v1.users.show');
        Route::get('roles', [UserController::class, 'roles'])->name('api.v1.roles.index');
        Route::post('users/{user}/roles', [UserController::class, 'assignRole'])->name('api.v1.users.roles.assign');
        Route::delete('users/{user}/roles/{role}', [UserController::class, 'removeRole'])->name('api.v1.users.roles.remove');

        // Store settings (admin only)
        Route::get('store-settings', [StoreSettingController::class, 'show'])->name('api.v1.store-settings.show');
        Route::put('store-settings', [StoreSettingController::class, 'update'])->name('api.v1.store-settings.update');
        Route::post('store-settings/logo', [StoreSettingController::class, 'uploadLogo'])->name('api.v1.store-settings.logo.upload');
        Route::delete('store-settings/logo', [StoreSettingController::class, 'deleteLogo'])->name('api.v1.store-settings.logo.destroy');
        Route::post('store-settings/qr-code', [StoreSettingController::class, 'uploadQrCode'])->name('api.v1.store-settings.qr-code.upload');
        Route::delete('store-settings/qr-code', [StoreSettingController::class, 'deleteQrCode'])->name('api.v1.store-settings.qr-code.destroy');
    });

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
