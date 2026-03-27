<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Repositories\Contracts\BrandRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final class BrandService
{
    public function __construct(
        private readonly BrandRepositoryInterface $brandRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min($filters['per_page'] ?? 15, 100);

        return $this->brandRepository->paginate($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Brand
    {
        /** @var Brand */
        return $this->brandRepository->create([
            ...$data,
            'slug' => Str::slug($data['name']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Brand $brand, array $data): Brand
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        /** @var Brand */
        return $this->brandRepository->update($brand, $data);
    }

    public function delete(Brand $brand): void
    {
        $this->brandRepository->delete($brand);
    }
}
