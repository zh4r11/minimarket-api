<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductVariantAttributeValue;
use App\Repositories\Contracts\ProductVariantAttributeValueRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ProductVariantAttributeValueService
{
    public function __construct(
        private readonly ProductVariantAttributeValueRepositoryInterface $valueRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $this->valueRepository->paginate($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProductVariantAttributeValue
    {
        /** @var ProductVariantAttributeValue $value */
        $value = $this->valueRepository->create($data);
        $value->load('attribute');

        return $value;
    }

    public function show(ProductVariantAttributeValue $value): ProductVariantAttributeValue
    {
        $value->load('attribute');

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProductVariantAttributeValue $value, array $data): ProductVariantAttributeValue
    {
        $this->valueRepository->update($value, $data);
        $value->load('attribute');

        return $value;
    }

    public function delete(ProductVariantAttributeValue $value): void
    {
        $this->valueRepository->delete($value);
    }
}
