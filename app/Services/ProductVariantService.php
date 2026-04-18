<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductVariant;
use App\Repositories\Contracts\ProductVariantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ProductVariantService
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $productVariantRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $this->productVariantRepository->paginate($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProductVariant
    {
        $attributeValueIds = $data['attribute_value_ids'] ?? [];
        unset($data['attribute_value_ids']);

        /** @var ProductVariant $variant */
        $variant = $this->productVariantRepository->create($data);

        if ($attributeValueIds !== []) {
            $variant->attributeValues()->sync($attributeValueIds);
        }

        $variant->load(['parent', 'attributeValues.attribute', 'photos']);

        return $variant;
    }

    public function show(ProductVariant $productVariant): ProductVariant
    {
        $productVariant->load(['parent', 'attributeValues.attribute', 'photos']);

        return $productVariant;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProductVariant $productVariant, array $data): ProductVariant
    {
        $attributeValueIds = $data['attribute_value_ids'] ?? null;
        unset($data['attribute_value_ids']);

        $this->productVariantRepository->update($productVariant, $data);

        if ($attributeValueIds !== null) {
            $productVariant->attributeValues()->sync($attributeValueIds);
        }

        $productVariant->load(['parent', 'attributeValues.attribute', 'photos']);

        return $productVariant;
    }

    public function delete(ProductVariant $productVariant): void
    {
        $this->productVariantRepository->delete($productVariant);
    }
}
