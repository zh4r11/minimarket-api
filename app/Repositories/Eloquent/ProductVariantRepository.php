<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ProductVariant;
use App\Repositories\Contracts\ProductVariantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<ProductVariant>
 */
final class ProductVariantRepository extends BaseRepository implements ProductVariantRepositoryInterface
{
    public function __construct(ProductVariant $model)
    {
        parent::__construct($model);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with(['parent', 'attributeValues.attribute', 'photos'])
            ->when($filters['parent_id'] ?? null, fn ($q) => $q->where('parent_id', $filters['parent_id']))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('sku', 'like', "%{$s}%"))
            ->when(array_key_exists('is_active', $filters), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->paginate($perPage);
    }
}
