<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementCollection;
use App\Http\Resources\StockCardCollection;
use App\Http\Resources\StockMovementResource;
use App\Http\Resources\StockSummaryResource;
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

    /**
     * Get stock movements for a product.
     *
     * Returns detailed stock movements with summary for a specific product.
     *
     * @queryParam start_date date Filter movements from this date. Example: 2026-01-01
     * @queryParam end_date date Filter movements until this date. Example: 2026-04-30
     * @queryParam types string Filter by movement types (comma-separated). Example: in,out,adjustment
     * @queryParam per_page integer Items per page. Example: 50
     * @queryParam page integer Page number. Example: 1
     */
    public function stockMovements(Request $request, Product $product): JsonResponse
    {
        $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'types' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:200',
            'page' => 'nullable|integer|min:1',
        ]);

        $result = $this->productService->getStockMovementsDetail($product, $filters);

        return $this->success([
            'product' => $result['product'],
            'summary' => new StockSummaryResource($result['summary']),
            'movements' => (new StockMovementCollection($result['movements']))->toArray($request),
        ]);
    }

    /**
     * Get stock card for a product.
     *
     * Returns a stock card (ledger) showing all stock movements with running balance.
     *
     * @queryParam start_date date Filter movements from this date. Example: 2026-01-01
     * @queryParam end_date date Filter movements until this date. Example: 2026-04-30
     */
    public function stockCard(Request $request, Product $product): JsonResponse
    {
        $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $result = $this->productService->getStockCard($product, $filters);

        return $this->success([
            'product' => $result['product'],
            'summary' => new StockSummaryResource($result['summary']),
            'entries' => (new StockCardCollection($result['entries']))->toArray($request),
        ]);
    }
}
