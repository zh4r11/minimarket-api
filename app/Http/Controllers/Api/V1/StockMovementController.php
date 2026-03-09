<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreStockMovementRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class StockMovementController extends ApiController
{
    /**
     * List stock movements.
     *
     * Returns a paginated list of stock movement records with product details. Supports search and filtering.
     *
     * @queryParam search string Search by notes. Example: penyesuaian
     * @queryParam product_id integer Filter by product ID. Example: 5
     * @queryParam type string Filter by movement type (in, out). Example: in
     * @queryParam per_page integer Number of items per page (max 100). Defaults to 15. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $movements = StockMovement::query()
            ->with(['product'])
            ->when($request->search, fn ($q) => $q->where('notes', 'like', "%{$request->search}%"))
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->paginate($perPage);

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
        $movement = DB::transaction(function () use ($request): StockMovement {
            $validated = $request->validated();

            $product = \App\Models\Product::query()->lockForUpdate()->findOrFail($validated['product_id']);
            $beforeStock = $product->stock;

            $afterStock = match ($validated['type']) {
                'in' => $beforeStock + $validated['quantity'],
                default => $beforeStock - $validated['quantity'],
            };

            $movement = StockMovement::query()->create([
                'product_id' => $validated['product_id'],
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'before_stock' => $beforeStock,
                'after_stock' => $afterStock,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $product->update(['stock' => $afterStock]);
            $movement->load('product');

            return $movement;
        });

        return $this->created(new StockMovementResource($movement));
    }

    /**
     * Get a stock movement.
     *
     * Returns the details of a specific stock movement record including the related product.
     */
    public function show(StockMovement $stockMovement): JsonResponse
    {
        $stockMovement->load('product');

        return $this->success(new StockMovementResource($stockMovement));
    }
}
