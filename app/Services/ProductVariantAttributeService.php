<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductVariantAttribute;
use App\Repositories\Contracts\ProductVariantAttributeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ProductVariantAttributeService
{
    public function __construct(
        private readonly ProductVariantAttributeRepositoryInterface $attributeRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $this->attributeRepository->paginate($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProductVariantAttribute
    {
        /** @var ProductVariantAttribute $attribute */
        $attribute = $this->attributeRepository->create($data);
        $attribute->load('values');

        return $attribute;
    }

    public function show(ProductVariantAttribute $attribute): ProductVariantAttribute
    {
        $attribute->load('values');

        return $attribute;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProductVariantAttribute $attribute, array $data): ProductVariantAttribute
    {
        $this->attributeRepository->update($attribute, $data);
        $attribute->load('values');

        return $attribute;
    }

    public function delete(ProductVariantAttribute $attribute): void
    {
        $this->attributeRepository->delete($attribute);
    }
}
