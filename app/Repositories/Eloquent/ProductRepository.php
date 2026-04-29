<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Bundle;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<Product>
 */
final class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;

        $paginator = $this->query()
            ->with(['category', 'brand', 'unit', 'photos'])
            ->where('type', '!=', 'variant')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"))
            ->when($filters['category_id'] ?? null, fn ($q) => $q->where('category_id', $filters['category_id']))
            ->when(array_key_exists('is_active', $filters), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->paginate($perPage);

        $this->swapBundlePhotos($paginator->getCollection());

        return $paginator;
    }

    public function findWithRelations(Product $product): Product
    {
        $product->load(['category', 'brand', 'unit', 'photos', 'variants.attributeValues.attribute']);

        if ($product->type === 'bundle') {
            $photos = ProductPhoto::query()
                ->where('photoable_type', Bundle::class)
                ->where('photoable_id', $product->id)
                ->orderBy('sort_order')
                ->get();
            $product->setRelation('photos', $photos);
        }

        return $product;
    }

    /**
     * Replace the eagerly-loaded photos for bundle products with photos stored
     * under the Bundle morph type, since bundles use App\Models\Bundle as the
     * photoable_type rather than App\Models\Product.
     *
     * @param  Collection<int, Product>  $products
     */
    private function swapBundlePhotos(Collection $products): void
    {
        $bundleIds = $products->where('type', 'bundle')->pluck('id');

        if ($bundleIds->isEmpty()) {
            return;
        }

        $photosByBundle = ProductPhoto::query()
            ->whereIn('photoable_id', $bundleIds)
            ->where('photoable_type', Bundle::class)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('photoable_id');

        $products->each(function (Product $product) use ($photosByBundle): void {
            if ($product->type === 'bundle') {
                $product->setRelation('photos', $photosByBundle->get($product->id, collect()));
            }
        });
    }

    public function findWithLockedStock(int $id): Product
    {
        /** @var Product */
        return $this->query()->lockForUpdate()->findOrFail($id);
    }

    public function stockReport(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $status = $filters['status'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $includeVariants = $filters['include_variants'] ?? false;

        $stockInQuery = DB::raw("(SELECT COALESCE(SUM(
            CASE
                WHEN type IN ('in', 'initial') THEN quantity
                WHEN type = 'adjustment' AND after_stock > before_stock THEN quantity
                WHEN type = 'purchase' THEN quantity
                ELSE 0
            END
        ), 0) FROM stock_movements WHERE stock_movements.product_id = products.id"
            . ($startDate ? " AND stock_movements.created_at >= '{$startDate}'" : '')
            . ($endDate ? " AND stock_movements.created_at <= '{$endDate}'" : '') . ') as stock_in');

        $stockOutQuery = DB::raw("(SELECT COALESCE(SUM(
            CASE
                WHEN type = 'out' THEN quantity
                WHEN type = 'adjustment' AND after_stock < before_stock THEN quantity
                WHEN type = 'sale' THEN quantity
                ELSE 0
            END
        ), 0) FROM stock_movements WHERE stock_movements.product_id = products.id"
            . ($startDate ? " AND stock_movements.created_at >= '{$startDate}'" : '')
            . ($endDate ? " AND stock_movements.created_at <= '{$endDate}'" : '') . ') as stock_out');

        $adjustmentQuery = DB::raw("(SELECT COALESCE(SUM(
            CASE
                WHEN type = 'adjustment' THEN ABS(after_stock - before_stock)
                ELSE 0
            END
        ), 0) FROM stock_movements WHERE stock_movements.product_id = products.id"
            . ($startDate ? " AND stock_movements.created_at >= '{$startDate}'" : '')
            . ($endDate ? " AND stock_movements.created_at <= '{$endDate}'" : '') . ') as stock_adjustment');

        $query = $this->query()
            ->with(['category', 'unit', 'parent'])
            ->select([
                'products.*',
                $stockInQuery,
                $stockOutQuery,
                $adjustmentQuery,
            ]);

        if (! $includeVariants) {
            $query->where('type', '!=', 'variant');
        }

        return $query
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('products.name', 'like', "%{$search}%")
                ->orWhere('products.sku', 'like', "%{$search}%")))
            ->when($filters['category_id'] ?? null, fn ($q) => $q->where('products.category_id', $filters['category_id']))
            ->when($filters['type'] ?? null, fn ($q) => $q->where('products.type', $filters['type']))
            ->when($status === 'low', fn ($q) => $q->whereRaw('products.stock <= products.min_stock AND products.stock > 0'))
            ->when($status === 'empty', fn ($q) => $q->where('products.stock', 0))
            ->when($status === 'over', fn ($q) => $q->whereRaw('products.stock > products.min_stock * 2'))
            ->orderBy('products.name')
            ->paginate($perPage);
    }
}
