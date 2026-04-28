<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Bundle;
use App\Models\BundleItem;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Support\Facades\Auth;

final class BundleStockService
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $stockMovementRepository,
    ) {}

    /**
     * @param  iterable<BundleItem>  $items
     */
    public function calculateFromItems(iterable $items): int
    {
        $itemStocks = collect($items)->map(function (BundleItem $item): int {
            // Use variant stock when a specific variant is set; otherwise fall back to product stock
            $componentStock = max(0, (int) ($item->variant?->stock ?? $item->product?->stock ?? 0));
            $componentQty = max(1, (int) $item->quantity);

            return intdiv($componentStock, $componentQty);
        });

        if ($itemStocks->isEmpty()) {
            return 0;
        }

        return max(0, (int) $itemStocks->min());
    }

    public function recalculateForBundle(Bundle $bundle): int
    {
        $bundle->loadMissing(['items.product', 'items.variant']);

        $calculatedStock = $this->calculateFromItems($bundle->items);

        if ((int) $bundle->stock !== $calculatedStock) {
            $beforeStock = (int) $bundle->stock;
            $stockDiff = $calculatedStock - $beforeStock;
            $bundle->forceFill(['stock' => $calculatedStock])->save();

            // Create stock movement for bundle stock change
            $this->stockMovementRepository->create([
                'product_id' => $bundle->id,
                'type' => $stockDiff >= 0 ? 'in' : 'out',
                'reference_type' => Bundle::class,
                'reference_id' => $bundle->id,
                'quantity' => abs($stockDiff),
                'before_stock' => $beforeStock,
                'after_stock' => $calculatedStock,
                'notes' => 'Bundle stock recalculation due to component stock change',
                'created_by' => Auth::id(),
            ]);
        }

        $bundle->setAttribute('stock', $calculatedStock);

        return $calculatedStock;
    }

    /**
     * @param  array<int, int>  $componentProductIds
     */
    public function recalculateAffectedBundlesByComponentIds(array $componentProductIds): void
    {
        $componentIds = collect($componentProductIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($componentIds->isEmpty()) {
            return;
        }

        $bundleIds = BundleItem::query()
            ->where(function ($q) use ($componentIds): void {
                $q->whereIn('product_id', $componentIds->all())
                    ->orWhereIn('variant_id', $componentIds->all());
            })
            ->distinct()
            ->pluck('bundle_id');

        if ($bundleIds->isEmpty()) {
            return;
        }

        Bundle::query()
            ->whereIn('id', $bundleIds)
            ->lockForUpdate()
            ->get()
            ->each(fn (Bundle $bundle): int => $this->recalculateForBundle($bundle));
    }
}
