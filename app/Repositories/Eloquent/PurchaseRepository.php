<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<Purchase>
 */
final class PurchaseRepository extends BaseRepository implements PurchaseRepositoryInterface
{
    public function __construct(Purchase $model)
    {
        parent::__construct($model);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with(['supplier', 'items.product'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('invoice_number', 'like', "%{$s}%")
                ->orWhere('notes', 'like', "%{$s}%"))
            ->when($filters['supplier_id'] ?? null, fn ($q) => $q->where('supplier_id', $filters['supplier_id']))
            ->when($filters['status'] ?? null, fn ($q) => $q->where('status', $filters['status']))
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function createItem(int $purchaseId, array $item): PurchaseItem
    {
        /** @var PurchaseItem */
        return PurchaseItem::query()->create([
            'purchase_id' => $purchaseId,
            'product_id' => $item['product_id'],
            'variant_id' => $item['variant_id'] ?? null,
            'quantity' => $item['quantity'],
            'buy_price' => $item['buy_price'],
            'subtotal' => $item['subtotal'],
        ]);
    }
}
