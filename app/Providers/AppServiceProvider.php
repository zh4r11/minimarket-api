<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Repositories\Contracts\BundleRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\ProductPhotoRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\ProductVariantAttributeRepositoryInterface;
use App\Repositories\Contracts\ProductVariantAttributeValueRepositoryInterface;
use App\Repositories\Contracts\ProductVariantRepositoryInterface;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use App\Repositories\Contracts\SaleRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Repositories\Contracts\UnitRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\BrandRepository;
use App\Repositories\Eloquent\BundleRepository;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\CustomerRepository;
use App\Repositories\Eloquent\ProductPhotoRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Repositories\Eloquent\ProductVariantAttributeRepository;
use App\Repositories\Eloquent\ProductVariantAttributeValueRepository;
use App\Repositories\Eloquent\ProductVariantRepository;
use App\Repositories\Eloquent\PurchaseRepository;
use App\Repositories\Eloquent\SaleRepository;
use App\Repositories\Eloquent\StockMovementRepository;
use App\Repositories\Eloquent\SupplierRepository;
use App\Repositories\Eloquent\UnitRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        BrandRepositoryInterface::class => BrandRepository::class,
        BundleRepositoryInterface::class => BundleRepository::class,
        CategoryRepositoryInterface::class => CategoryRepository::class,
        CustomerRepositoryInterface::class => CustomerRepository::class,
        ProductPhotoRepositoryInterface::class => ProductPhotoRepository::class,
        ProductRepositoryInterface::class => ProductRepository::class,
        ProductVariantAttributeRepositoryInterface::class => ProductVariantAttributeRepository::class,
        ProductVariantAttributeValueRepositoryInterface::class => ProductVariantAttributeValueRepository::class,
        ProductVariantRepositoryInterface::class => ProductVariantRepository::class,
        PurchaseRepositoryInterface::class => PurchaseRepository::class,
        SaleRepositoryInterface::class => SaleRepository::class,
        StockMovementRepositoryInterface::class => StockMovementRepository::class,
        SupplierRepositoryInterface::class => SupplierRepository::class,
        UnitRepositoryInterface::class => UnitRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    private function configureRateLimiting(): void
    {
        // Default API rate limiter - 60 requests per minute
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        // Auth endpoints - more restrictive (prevent brute force)
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // Authenticated user requests - higher limit
        RateLimiter::for('authenticated', fn (Request $request) => $request->user()
            ? Limit::perMinute(120)->by($request->user()->id)
            : Limit::perMinute(60)->by($request->ip()));
    }
}
