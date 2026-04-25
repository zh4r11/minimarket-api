<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreStockAdjustmentRequest;
use App\Http\Requests\Api\V1\StoreStockMovementRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use App\Services\StockMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StockMovementController extends ApiController
{
    public function __construct(
        private readonly StockMovementService $stockMovementService,
    ) {}

    /**
     * List stock movements.
     *
     * Returns a paginated list of stock movement records with product details. Supports search and filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search'     => 'nullable|string',
            'product_id' => 'nullable|integer|exists:products,id',
            'type'       => 'nullable|string|in:in,out,initial,adjustment,sale,purchase',
            'per_page'   => 'nullable|integer|min:1|max:100',
            'page'       => 'nullable|integer|min:1',
        ]);

        $movements = $this->stockMovementService->list($filters);

        return $this->success(StockMovementResource::collection($movements)->toResponse($request)->getData(true));
    }

    /**
     * Create a stock movement.
     *
     * Records a stock adjustment (in or out) for a product.
     * Updates the product's current stock and stores the before/after stock snapshot.
     */
    public function store(StoreStockMovementRequest $request): JsonResponse
    {
        $movement = $this->stockMovementService->create($request->validated());

        return $this->created(new StockMovementResource($movement));
    }

    /**
     * Get a stock movement.
     *
     * Returns the details of a specific stock movement record including the related product.
     */
    public function show(StockMovement $stockMovement): JsonResponse
    {
        $stockMovement = $this->stockMovementService->show($stockMovement);

        return $this->success(new StockMovementResource($stockMovement));
    }

    /**
     * Stock adjustment.
     *
     * Adjusts the stock of a product to a specific actual count (e.g. after a physical stock count).
     * Records the before/after snapshot and the difference as an adjustment movement.
     *
     * @bodyParam product_id integer required The product ID. Example: 1
     * @bodyParam actual_stock integer required The actual stock count after physical verification. Must be >= 0. Example: 48
     * @bodyParam notes string optional Reason for adjustment. Example: Stock opname Maret 2026
     */
    public function adjust(StoreStockAdjustmentRequest $request): JsonResponse
    {
        $movement = $this->stockMovementService->adjust($request->validated());

        return $this->created(new StockMovementResource($movement));
    }
}
