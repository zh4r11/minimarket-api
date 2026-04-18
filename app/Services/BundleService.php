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
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $this->bundleRepository->paginate($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Bundle
    {
        $items = $data['items'];
        unset($data['items']);

        $bundle = DB::transaction(function () use ($data, $items): Bundle {
            /** @var Bundle $bundle */
            $bundle = $this->bundleRepository->create($data);
            $bundle->items()->createMany($items);

            return $bundle;
        });

        $bundle->load(['items.product']);

        return $bundle;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Bundle $bundle, array $data): Bundle
    {
        $items = $data['items'] ?? null;
        unset($data['items']);

        $this->bundleRepository->update($bundle, $data);

        if ($items !== null) {
            $bundle->items()->delete();
            $bundle->items()->createMany($items);
        }

        $bundle->load(['items.product']);

        return $bundle;
    }

    public function delete(Bundle $bundle): void
    {
        $this->bundleRepository->delete($bundle);
    }
}
