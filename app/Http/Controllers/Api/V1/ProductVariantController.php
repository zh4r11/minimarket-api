<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreProductVariantRequest;
use App\Http\Requests\Api\V1\UpdateProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductVariantController extends ApiController
{
    /**
     * List product variants.
     *
     * Returns a paginated list of product variants with their attribute values.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'parent_id' => 'nullable|integer|exists:products,id',
            'search'    => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'per_page'  => 'nullable|integer|min:1|max:100',
            'page'      => 'nullable|integer|min:1',
        ]);

        $perPage = min($filters['per_page'] ?? 15, 100);

        $variants = ProductVariant::query()
            ->with(['parent', 'attributeValues.attribute', 'photos'])
            ->when($filters['parent_id'] ?? null, fn ($q) => $q->where('parent_id', $filters['parent_id']))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('sku', 'like', "%{$s}%"))
            ->when(array_key_exists('is_active', $filters), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->paginate($perPage);

        return $this->success(ProductVariantResource::collection($variants)->toResponse($request)->getData(true));
    }

    /**
     * Create a product variant.
     *
     * Stores a new variant for a product with optional attribute values.
     */
    public function store(StoreProductVariantRequest $request): JsonResponse
    {
        $data = $request->validated();
        $attributeValueIds = $data['attribute_value_ids'] ?? [];
        unset($data['attribute_value_ids']);

        $variant = ProductVariant::query()->create($data);

        if ($attributeValueIds !== []) {
            $variant->attributeValues()->sync($attributeValueIds);
        }

        $variant->load(['parent', 'attributeValues.attribute', 'photos']);

        return $this->created(new ProductVariantResource($variant));
    }

    /**
     * Get a product variant.
     *
     * Returns the details of a specific product variant including attribute values.
     */
    public function show(ProductVariant $productVariant): JsonResponse
    {
        $productVariant->load(['parent', 'attributeValues.attribute', 'photos']);

        return $this->success(new ProductVariantResource($productVariant));
    }

    /**
     * Update a product variant.
     *
     * Updates the specified product variant. Pass attribute_value_ids to replace all assigned attribute values.
     */
    public function update(UpdateProductVariantRequest $request, ProductVariant $productVariant): JsonResponse
    {
        $data = $request->validated();
        $attributeValueIds = $data['attribute_value_ids'] ?? null;
        unset($data['attribute_value_ids']);

        $productVariant->update($data);

        if ($attributeValueIds !== null) {
            $productVariant->attributeValues()->sync($attributeValueIds);
        }

        $productVariant->load(['parent', 'attributeValues.attribute', 'photos']);

        return $this->success(new ProductVariantResource($productVariant));
    }

    /**
     * Delete a product variant.
     *
     * Permanently deletes the specified product variant.
     */
    public function destroy(ProductVariant $productVariant): JsonResponse
    {
        $productVariant->delete();

        return $this->noContent();
    }
}
