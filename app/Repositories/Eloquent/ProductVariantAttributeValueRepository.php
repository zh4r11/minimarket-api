<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ProductVariantAttributeValue;
use App\Repositories\Contracts\ProductVariantAttributeValueRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<ProductVariantAttributeValue>
 */
final class ProductVariantAttributeValueRepository extends BaseRepository implements ProductVariantAttributeValueRepositoryInterface
{
    public function __construct(ProductVariantAttributeValue $model)
    {
        parent::__construct($model);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with('attribute')
            ->when($filters['attribute_id'] ?? null, fn ($q) => $q->where('attribute_id', $filters['attribute_id']))
            ->when($filters['search'] ?? null, fn ($q) => $q->where('value', 'like', "%{$filters['search']}%"))
            ->paginate($perPage);
    }
}
