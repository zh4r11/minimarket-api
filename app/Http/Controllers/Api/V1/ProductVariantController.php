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
     *
     * @queryParam product_id integer Filter by product ID. Example: 1
     * @queryParam search string Search by SKU. Example: SKU-VAR
     * @queryParam is_active boolean Filter by active status (true/false). Example: true
     * @queryParam per_page integer Number of items per page (max 100). Defaults to 15. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $variants = ProductVariant::query()
            ->with(['product.photos', 'attributeValues.attribute'])
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->search, fn ($q) => $q->where('sku', 'like', "%{$request->search}%"))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
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

        $variant->load(['product.photos', 'attributeValues.attribute']);

        return $this->created(new ProductVariantResource($variant));
    }

    /**
     * Get a product variant.
     *
     * Returns the details of a specific product variant including attribute values.
     */
    public function show(ProductVariant $productVariant): JsonResponse
    {
        $productVariant->load(['product.photos', 'attributeValues.attribute']);

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

        $productVariant->load(['product.photos', 'attributeValues.attribute']);

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
