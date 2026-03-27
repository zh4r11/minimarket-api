<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreProductVariantAttributeRequest;
use App\Http\Requests\Api\V1\UpdateProductVariantAttributeRequest;
use App\Http\Resources\ProductVariantAttributeResource;
use App\Models\ProductVariantAttribute;
use App\Services\ProductVariantAttributeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductVariantAttributeController extends ApiController
{
    public function __construct(
        private readonly ProductVariantAttributeService $attributeService,
    ) {}

    /**
     * List variant attributes.
     *
     * Returns a paginated list of variant attributes with their values.
     *
     * @queryParam search string Search by name. Example: Ukuran
     * @queryParam per_page integer Number of items per page (max 100). Defaults to 15. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search'   => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page'     => 'nullable|integer|min:1',
        ]);

        $attributes = $this->attributeService->list($filters);

        return $this->success(ProductVariantAttributeResource::collection($attributes)->toResponse($request)->getData(true));
    }

    /**
     * Create a variant attribute.
     *
     * Stores a new variant attribute (e.g. "Ukuran", "Rasa", "Warna").
     */
    public function store(StoreProductVariantAttributeRequest $request): JsonResponse
    {
        $attribute = $this->attributeService->create($request->validated());

        return $this->created(new ProductVariantAttributeResource($attribute));
    }

    /**
     * Get a variant attribute.
     *
     * Returns the details of a specific variant attribute including its values.
     */
    public function show(ProductVariantAttribute $productVariantAttribute): JsonResponse
    {
        $productVariantAttribute = $this->attributeService->show($productVariantAttribute);

        return $this->success(new ProductVariantAttributeResource($productVariantAttribute));
    }

    /**
     * Update a variant attribute.
     *
     * Updates the specified variant attribute.
     */
    public function update(UpdateProductVariantAttributeRequest $request, ProductVariantAttribute $productVariantAttribute): JsonResponse
    {
        $productVariantAttribute = $this->attributeService->update($productVariantAttribute, $request->validated());

        return $this->success(new ProductVariantAttributeResource($productVariantAttribute));
    }

    /**
     * Delete a variant attribute.
     *
     * Permanently deletes the specified variant attribute and all its values.
     */
    public function destroy(ProductVariantAttribute $productVariantAttribute): JsonResponse
    {
        $this->attributeService->delete($productVariantAttribute);

        return $this->noContent();
    }
}
