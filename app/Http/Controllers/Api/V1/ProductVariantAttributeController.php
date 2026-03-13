<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreProductVariantAttributeRequest;
use App\Http\Requests\Api\V1\UpdateProductVariantAttributeRequest;
use App\Http\Resources\ProductVariantAttributeResource;
use App\Models\ProductVariantAttribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductVariantAttributeController extends ApiController
{
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
        $perPage = min($request->integer('per_page', 15), 100);

        $attributes = ProductVariantAttribute::query()
            ->with('values')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->paginate($perPage);

        return $this->success(ProductVariantAttributeResource::collection($attributes)->toResponse($request)->getData(true));
    }

    /**
     * Create a variant attribute.
     *
     * Stores a new variant attribute (e.g. "Ukuran", "Rasa", "Warna").
     */
    public function store(StoreProductVariantAttributeRequest $request): JsonResponse
    {
        $attribute = ProductVariantAttribute::query()->create($request->validated());
        $attribute->load('values');

        return $this->created(new ProductVariantAttributeResource($attribute));
    }

    /**
     * Get a variant attribute.
     *
     * Returns the details of a specific variant attribute including its values.
     */
    public function show(ProductVariantAttribute $productVariantAttribute): JsonResponse
    {
        $productVariantAttribute->load('values');

        return $this->success(new ProductVariantAttributeResource($productVariantAttribute));
    }

    /**
     * Update a variant attribute.
     *
     * Updates the specified variant attribute.
     */
    public function update(UpdateProductVariantAttributeRequest $request, ProductVariantAttribute $productVariantAttribute): JsonResponse
    {
        $productVariantAttribute->update($request->validated());
        $productVariantAttribute->load('values');

        return $this->success(new ProductVariantAttributeResource($productVariantAttribute));
    }

    /**
     * Delete a variant attribute.
     *
     * Permanently deletes the specified variant attribute and all its values.
     */
    public function destroy(ProductVariantAttribute $productVariantAttribute): JsonResponse
    {
        $productVariantAttribute->delete();

        return $this->noContent();
    }
}
