<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Bundle;
use App\Repositories\Contracts\BundleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<Bundle>
 */
final class BundleRepository extends BaseRepository implements BundleRepositoryInterface
{
    public function __construct(Bundle $model)
    {
        parent::__construct($model);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with(['items.product', 'photos'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('sku', 'like', "%{$s}%"))
            ->when(array_key_exists('is_active', $filters), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->paginate($perPage);
    }
}
