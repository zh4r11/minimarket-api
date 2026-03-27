<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreProductVariantRequest;
use App\Http\Requests\Api\V1\UpdateProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\ProductVariant;
use App\Services\ProductVariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductVariantController extends ApiController
{
    public function __construct(
        private readonly ProductVariantService $productVariantService,
    ) {}

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

        $variants = $this->productVariantService->list($filters);

        return $this->success(ProductVariantResource::collection($variants)->toResponse($request)->getData(true));
    }

    /**
     * Create a product variant.
     *
     * Stores a new variant for a product with optional attribute values.
     */
    public function store(StoreProductVariantRequest $request): JsonResponse
    {
        $variant = $this->productVariantService->create($request->validated());

        return $this->created(new ProductVariantResource($variant));
    }

    /**
     * Get a product variant.
     *
     * Returns the details of a specific product variant including attribute values.
     */
    public function show(ProductVariant $productVariant): JsonResponse
    {
        $productVariant = $this->productVariantService->show($productVariant);

        return $this->success(new ProductVariantResource($productVariant));
    }

    /**
     * Update a product variant.
     *
     * Updates the specified product variant. Pass attribute_value_ids to replace all assigned attribute values.
     */
    public function update(UpdateProductVariantRequest $request, ProductVariant $productVariant): JsonResponse
    {
        $productVariant = $this->productVariantService->update($productVariant, $request->validated());

        return $this->success(new ProductVariantResource($productVariant));
    }

    /**
     * Delete a product variant.
     *
     * Permanently deletes the specified product variant.
     */
    public function destroy(ProductVariant $productVariant): JsonResponse
    {
        $this->productVariantService->delete($productVariant);

        return $this->noContent();
    }
}
