<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Bundle;
use App\Models\BundleItem;

final class BundleStockService
{
    /**
     * @param  iterable<BundleItem>  $items
     */
    public function calculateFromItems(iterable $items): int
    {
        $itemStocks = collect($items)->map(function (BundleItem $item): int {
            $componentStock = max(0, (int) ($item->product?->stock ?? 0));
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
        $bundle->loadMissing(['items.product']);

        $calculatedStock = $this->calculateFromItems($bundle->items);

        if ((int) $bundle->stock !== $calculatedStock) {
            $bundle->forceFill(['stock' => $calculatedStock])->save();
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
            ->whereIn('product_id', $componentIds)
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
