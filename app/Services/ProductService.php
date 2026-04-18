<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StockMovementRepositoryInterface $stockMovementRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $this->productRepository->paginateWithFilters($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $initialStock = $data['stock'] ?? 0;

            /** @var Product $product */
            $product = $this->productRepository->create(
                collect($data)->except('initial_stock_notes')->all()
            );

            if ($initialStock > 0) {
                $this->stockMovementRepository->create([
                    'product_id' => $product->id,
                    'type' => 'initial',
                    'reference_type' => Product::class,
                    'reference_id' => $product->id,
                    'quantity' => $initialStock,
                    'before_stock' => 0,
                    'after_stock' => $initialStock,
                    'notes' => $data['initial_stock_notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);
            }

            $product->load(['category', 'brand', 'unit', 'photos']);

            return $product;
        });
    }

    public function show(Product $product): Product
    {
        return $this->productRepository->findWithRelations($product);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        $this->productRepository->update($product, $data);
        $product->load(['category', 'brand', 'unit', 'photos']);

        return $product;
    }

    public function delete(Product $product): void
    {
        $this->productRepository->delete($product);
    }
}
