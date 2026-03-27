<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductController extends ApiController
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    /**
     * List products.
     *
     * Returns a paginated list of products with their category and unit. Supports search and filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search'      => 'nullable|string',
            'category_id' => 'nullable|integer|exists:categories,id',
            'is_active'   => 'nullable|boolean',
            'per_page'    => 'nullable|integer|min:1|max:100',
            'page'        => 'nullable|integer|min:1',
        ]);

        $products = $this->productService->list($filters);

        return $this->success(ProductResource::collection($products)->toResponse($request)->getData(true));
    }

    /**
     * Create a product.
     *
     * Stores a new product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return $this->created(new ProductResource($product));
    }

    /**
     * Get a product.
     *
     * Returns the details of a specific product including category and unit.
     */
    public function show(Product $product): JsonResponse
    {
        $product = $this->productService->show($product);

        return $this->success(new ProductResource($product));
    }

    /**
     * Update a product.
     *
     * Updates the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->update($product, $request->validated());

        return $this->success(new ProductResource($product));
    }

    /**
     * Delete a product.
     *
     * Permanently deletes the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return $this->noContent();
    }
}
