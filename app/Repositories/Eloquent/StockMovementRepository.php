<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<StockMovement>
 */
final class StockMovementRepository extends BaseRepository implements StockMovementRepositoryInterface
{
    public function __construct(StockMovement $model)
    {
        parent::__construct($model);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with(['product'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('notes', 'like', "%{$s}%"))
            ->when($filters['product_id'] ?? null, fn ($q) => $q->where('product_id', $filters['product_id']))
            ->when($filters['type'] ?? null, fn ($q) => $q->where('type', $filters['type']))
            ->paginate($perPage);
    }
}
