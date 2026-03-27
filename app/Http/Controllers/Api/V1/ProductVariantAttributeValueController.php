<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreProductVariantAttributeValueRequest;
use App\Http\Requests\Api\V1\UpdateProductVariantAttributeValueRequest;
use App\Http\Resources\ProductVariantAttributeValueResource;
use App\Models\ProductVariantAttributeValue;
use App\Services\ProductVariantAttributeValueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductVariantAttributeValueController extends ApiController
{
    public function __construct(
        private readonly ProductVariantAttributeValueService $valueService,
    ) {}

    /**
     * List variant attribute values.
     *
     * Returns a paginated list of variant attribute values.
     *
     * @queryParam attribute_id integer Filter by attribute ID. Example: 1
     * @queryParam search string Search by value. Example: 500ml
     * @queryParam per_page integer Number of items per page (max 100). Defaults to 15. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'attribute_id' => 'nullable|integer|exists:product_variant_attributes,id',
            'search'       => 'nullable|string',
            'per_page'     => 'nullable|integer|min:1|max:100',
            'page'         => 'nullable|integer|min:1',
        ]);

        $values = $this->valueService->list($filters);

        return $this->success(ProductVariantAttributeValueResource::collection($values)->toResponse($request)->getData(true));
    }

    /**
     * Create a variant attribute value.
     *
     * Stores a new value for a variant attribute (e.g. "250ml", "500ml" for attribute "Ukuran").
     */
    public function store(StoreProductVariantAttributeValueRequest $request): JsonResponse
    {
        $value = $this->valueService->create($request->validated());

        return $this->created(new ProductVariantAttributeValueResource($value));
    }

    /**
     * Get a variant attribute value.
     *
     * Returns the details of a specific variant attribute value.
     */
    public function show(ProductVariantAttributeValue $productVariantAttributeValue): JsonResponse
    {
        $productVariantAttributeValue = $this->valueService->show($productVariantAttributeValue);

        return $this->success(new ProductVariantAttributeValueResource($productVariantAttributeValue));
    }

    /**
     * Update a variant attribute value.
     *
     * Updates the specified variant attribute value.
     */
    public function update(UpdateProductVariantAttributeValueRequest $request, ProductVariantAttributeValue $productVariantAttributeValue): JsonResponse
    {
        $productVariantAttributeValue = $this->valueService->update($productVariantAttributeValue, $request->validated());

        return $this->success(new ProductVariantAttributeValueResource($productVariantAttributeValue));
    }

    /**
     * Delete a variant attribute value.
     *
     * Permanently deletes the specified variant attribute value.
     */
    public function destroy(ProductVariantAttributeValue $productVariantAttributeValue): JsonResponse
    {
        $this->valueService->delete($productVariantAttributeValue);

        return $this->noContent();
    }
}
