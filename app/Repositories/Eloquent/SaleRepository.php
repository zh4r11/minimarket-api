<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<Sale>
 */
final class SaleRepository extends BaseRepository implements SaleRepositoryInterface
{
    public function __construct(Sale $model)
    {
        parent::__construct($model);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with(['cashier', 'items.product'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('invoice_number', 'like', "%{$s}%")
                ->orWhere('notes', 'like', "%{$s}%"))
            ->when($filters['status'] ?? null, fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['payment_method'] ?? null, fn ($q) => $q->where('payment_method', $filters['payment_method']))
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function createItem(int $saleId, array $item): SaleItem
    {
        /** @var SaleItem */
        return SaleItem::query()->create([
            'sale_id' => $saleId,
            ...$item,
        ]);
    }
}
