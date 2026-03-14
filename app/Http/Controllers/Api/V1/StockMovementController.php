<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreStockAdjustmentRequest;
use App\Http\Requests\Api\V1\StoreStockMovementRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
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
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search'     => 'nullable|string',
            'product_id' => 'nullable|integer|exists:products,id',
            'type'       => 'nullable|string|in:in,out,initial,adjustment',
            'per_page'   => 'nullable|integer|min:1|max:100',
            'page'       => 'nullable|integer|min:1',
        ]);

        $perPage = min($filters['per_page'] ?? 15, 100);

        $movements = StockMovement::query()
            ->with(['product'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('notes', 'like', "%{$s}%"))
            ->when($filters['product_id'] ?? null, fn ($q) => $q->where('product_id', $filters['product_id']))
            ->when($filters['type'] ?? null, fn ($q) => $q->where('type', $filters['type']))
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

            $product = Product::query()->lockForUpdate()->findOrFail($validated['product_id']);
            $beforeStock = $product->stock;

            $afterStock = match ($validated['type']) {
                'in' => $beforeStock + $validated['quantity'],
                default => $beforeStock - $validated['quantity'],
            };

            $movement = StockMovement::query()->create([
                'product_id' => $product->id,
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
        $movement = DB::transaction(function () use ($request): StockMovement {
            $validated = $request->validated();

            $product = Product::query()->lockForUpdate()->findOrFail($validated['product_id']);
            $beforeStock = $product->stock;
            $actualStock = $validated['actual_stock'];
            $diff = abs($actualStock - $beforeStock);

            $movement = StockMovement::query()->create([
                'product_id' => $product->id,
                'type' => 'adjustment',
                'quantity' => $diff === 0 ? 0 : $diff,
                'before_stock' => $beforeStock,
                'after_stock' => $actualStock,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $product->update(['stock' => $actualStock]);
            $movement->load('product');

            return $movement;
        });

        return $this->created(new StockMovementResource($movement));
    }
}
