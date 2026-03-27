<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ProductVariantAttribute;
use App\Repositories\Contracts\ProductVariantAttributeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<ProductVariantAttribute>
 */
final class ProductVariantAttributeRepository extends BaseRepository implements ProductVariantAttributeRepositoryInterface
{
    public function __construct(ProductVariantAttribute $model)
    {
        parent::__construct($model);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with('values')
            ->when($filters['search'] ?? null, fn ($q) => $q->where('name', 'like', "%{$filters['search']}%"))
            ->paginate($perPage);
    }
}
