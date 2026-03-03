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
    public function index(Request $request): JsonResponse
    {
        $movements = StockMovement::query()
            ->with(['product'])
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->paginate(15);

        return $this->success(StockMovementResource::collection($movements)->toResponse($request)->getData(true));
    }

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

    public function show(StockMovement $stockMovement): JsonResponse
    {
        $stockMovement->load('product');

        return $this->success(new StockMovementResource($stockMovement));
    }
}
