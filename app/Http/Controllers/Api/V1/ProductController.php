<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ProductController extends ApiController
{
    /**
     * List products.
     *
     * Returns a paginated list of products with their category and unit. Supports search and filtering.
     *
     * @queryParam search string Search by name, SKU, or description. Example: Mie Goreng
     * @queryParam category_id integer Filter by category ID. Example: 1
     * @queryParam is_active boolean Filter by active status (true/false). Example: true
     * @queryParam per_page integer Number of items per page (max 100). Defaults to 15. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $products = Product::query()
            ->with(['category', 'brand', 'unit', 'photos'])
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('sku', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%"))
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->paginate($perPage);

        return $this->success(ProductResource::collection($products)->toResponse($request)->getData(true));
    }

    /**
     * Create a product.
     *
     * Stores a new product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = DB::transaction(function () use ($request): Product {
            $validated = $request->validated();
            $initialStock = $validated['stock'] ?? 0;

            $product = Product::query()->create(
                collect($validated)->except('initial_stock_notes')->all()
            );

            if ($initialStock > 0) {
                StockMovement::query()->create([
                    'product_id' => $product->id,
                    'type' => 'initial',
                    'reference_type' => Product::class,
                    'reference_id' => $product->id,
                    'quantity' => $initialStock,
                    'before_stock' => 0,
                    'after_stock' => $initialStock,
                    'notes' => $validated['initial_stock_notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);
            }

            $product->load(['category', 'brand', 'unit', 'photos']);

            return $product;
        });

        return $this->created(new ProductResource($product));
    }

    /**
     * Get a product.
     *
     * Returns the details of a specific product including category and unit.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'brand', 'unit', 'photos', 'variants.attributeValues.attribute']);

        return $this->success(new ProductResource($product));
    }

    /**
     * Update a product.
     *
     * Updates the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());
        $product->load(['category', 'brand', 'unit', 'photos']);

        return $this->success(new ProductResource($product));
    }

    /**
     * Delete a product.
     *
     * Permanently deletes the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return $this->noContent();
    }
}
