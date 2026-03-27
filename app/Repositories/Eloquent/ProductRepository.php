<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

        return $this->query()
            ->with(['category', 'brand', 'unit', 'photos'])
            ->where('type', '!=', 'variant')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"))
            ->when($filters['category_id'] ?? null, fn ($q) => $q->where('category_id', $filters['category_id']))
            ->when(array_key_exists('is_active', $filters), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->paginate($perPage);
    }

    public function findWithRelations(Product $product): Product
    {
        $product->load(['category', 'brand', 'unit', 'photos', 'variants.attributeValues.attribute']);

        return $product;
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

        return $this->query()
            ->with(['category', 'unit'])
            ->select([
                'products.*',
                DB::raw("(SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE stock_movements.product_id = products.id AND stock_movements.type IN ('in', 'initial')) as stock_in"),
                DB::raw("(SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE stock_movements.product_id = products.id AND stock_movements.type = 'out') as stock_out"),
            ])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('products.name', 'like', "%{$search}%")
                ->orWhere('products.sku', 'like', "%{$search}%")))
            ->when($filters['category_id'] ?? null, fn ($q) => $q->where('products.category_id', $filters['category_id']))
            ->when($status === 'low', fn ($q) => $q->whereRaw('products.stock <= products.min_stock AND products.stock > 0'))
            ->when($status === 'empty', fn ($q) => $q->where('products.stock', 0))
            ->paginate($perPage);
    }
}
