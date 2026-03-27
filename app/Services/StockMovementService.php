<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class StockMovementService
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $stockMovementRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min($filters['per_page'] ?? 15, 100);

        return $this->stockMovementRepository->paginate($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): StockMovement
    {
        return DB::transaction(function () use ($data): StockMovement {
            $product = Product::query()->lockForUpdate()->findOrFail($data['product_id']);
            $beforeStock = $product->stock;

            $afterStock = match ($data['type']) {
                'in' => $beforeStock + $data['quantity'],
                default => $beforeStock - $data['quantity'],
            };

            /** @var StockMovement $movement */
            $movement = $this->stockMovementRepository->create([
                'product_id' => $product->id,
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'before_stock' => $beforeStock,
                'after_stock' => $afterStock,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $product->update(['stock' => $afterStock]);
            $movement->load('product');

            return $movement;
        });
    }

    public function show(StockMovement $stockMovement): StockMovement
    {
        $stockMovement->load('product');

        return $stockMovement;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function adjust(array $data): StockMovement
    {
        return DB::transaction(function () use ($data): StockMovement {
            $product = Product::query()->lockForUpdate()->findOrFail($data['product_id']);
            $beforeStock = $product->stock;
            $actualStock = $data['actual_stock'];
            $diff = abs($actualStock - $beforeStock);

            /** @var StockMovement $movement */
            $movement = $this->stockMovementRepository->create([
                'product_id' => $product->id,
                'type' => 'adjustment',
                'quantity' => $diff === 0 ? 0 : $diff,
                'before_stock' => $beforeStock,
                'after_stock' => $actualStock,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $product->update(['stock' => $actualStock]);
            $movement->load('product');

            return $movement;
        });
    }
}
