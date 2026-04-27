<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Bundle;
use App\Repositories\Contracts\BundleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class BundleService
{
    public function __construct(
        private readonly BundleRepositoryInterface $bundleRepository,
        private readonly BundleStockService $bundleStockService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        $bundles = $this->bundleRepository->paginate($filters, $perPage);

        $bundles->getCollection()->each(
            fn (Bundle $bundle): int => $this->bundleStockService->recalculateForBundle($bundle)
        );

        return $bundles;
    }

    public function show(Bundle $bundle): Bundle
    {
        $bundle->load(['items.product', 'items.variant', 'photos']);
        $this->bundleStockService->recalculateForBundle($bundle);

        return $bundle;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Bundle
    {
        $items = $this->normalizeItems($data['items']);
        unset($data['items']);

        return DB::transaction(function () use ($data, $items): Bundle {
            /** @var Bundle $bundle */
            $bundle = $this->bundleRepository->create($data);
            $bundle->items()->createMany($items);
            $bundle->load(['items.product', 'items.variant', 'photos']);
            $this->bundleStockService->recalculateForBundle($bundle);

            return $bundle;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Bundle $bundle, array $data): Bundle
    {
        $items = array_key_exists('items', $data)
            ? $this->normalizeItems($data['items'])
            : null;
        unset($data['items']);

        return DB::transaction(function () use ($bundle, $data, $items): Bundle {
            $this->bundleRepository->update($bundle, $data);

            if ($items !== null) {
                $bundle->items()->delete();
                $bundle->items()->createMany($items);
            }

            $bundle->load(['items.product', 'items.variant', 'photos']);
            $this->bundleStockService->recalculateForBundle($bundle);

            return $bundle;
        });
    }

    public function delete(Bundle $bundle): void
    {
        $this->bundleRepository->delete($bundle);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{product_id: int, variant_id: int|null, quantity: int}>
     */
    private function normalizeItems(array $items): array
    {
        return array_map(
            static fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'variant_id' => isset($item['variant_id']) && $item['variant_id'] !== null
                    ? (int) $item['variant_id']
                    : null,
                'quantity' => (int) $item['quantity'],
            ],
            $items
        );
    }
}
