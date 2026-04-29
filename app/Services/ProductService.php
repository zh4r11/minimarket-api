<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Bundle;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\StockMovement;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
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

        if ($product->type === 'bundle') {
            $photos = ProductPhoto::query()
                ->where('photoable_type', Bundle::class)
                ->where('photoable_id', $product->id)
                ->orderBy('sort_order')
                ->get();
            $product->setRelation('photos', $photos);
        }

        return $product;
    }

    public function delete(Product $product): void
    {
        $this->productRepository->delete($product);
    }

    /**
     * Get stock movements detail for a product.
     *
     * @param  array<string, mixed>  $filters
     * @return array{summary: array<string, mixed>, movements: LengthAwarePaginator<StockMovement>}
     */
    public function getStockMovementsDetail(Product $product, array $filters): array
    {
        $perPage = min((int) ($filters['per_page'] ?? 50), 200);
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $types = $filters['types'] ?? null;

        $query = $this->stockMovementRepository->query()
            ->where('product_id', $product->id)
            ->with(['creator', 'product'])
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
            ->when($types, fn ($q) => $q->whereIn('type', explode(',', $types)))
            ->orderBy('created_at', 'desc');

        $movements = $query->paginate($perPage);

        // Calculate summary
        $summary = $this->calculateStockMovementSummary($product->id, $startDate, $endDate, $types);

        return [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => $product->stock,
            ],
            'summary' => $summary,
            'movements' => $movements,
        ];
    }

    /**
     * Get stock card for a product.
     *
     * @param  array<string, mixed>  $filters
     * @return array{product: array<string, mixed>, summary: array<string, mixed>, entries: Collection<int, array<string, mixed>>}
     */
    public function getStockCard(Product $product, array $filters): array
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $query = $this->stockMovementRepository->query()
            ->where('product_id', $product->id)
            ->with(['creator'])
            ->orderBy('created_at', 'asc');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $movements = $query->get();

        // Calculate beginning balance (before start date)
        $beginningBalance = 0;
        if ($startDate) {
            $previousMovements = $this->stockMovementRepository->query()
                ->where('product_id', $product->id)
                ->where('created_at', '<', $startDate)
                ->get();

            foreach ($previousMovements as $movement) {
                if (in_array($movement->type, ['in', 'initial', 'purchase'])) {
                    $beginningBalance += $movement->quantity;
                } elseif (in_array($movement->type, ['out', 'sale'])) {
                    $beginningBalance -= $movement->quantity;
                } elseif ($movement->type === 'adjustment') {
                    if ($movement->after_stock > $movement->before_stock) {
                        $beginningBalance += $movement->quantity;
                    } else {
                        $beginningBalance -= $movement->quantity;
                    }
                }
            }
        }

        // Build entries with running balance
        $entries = new Collection;
        $runningBalance = $beginningBalance;

        // Add beginning balance entry
        if ($startDate && $movements->isNotEmpty()) {
            $entries->push([
                'date' => $startDate,
                'ref' => 'SALDO AWAL',
                'type' => 'initial',
                'quantity_in' => $beginningBalance,
                'quantity_out' => 0,
                'balance' => $beginningBalance,
                'notes' => 'Saldo awal per '.$startDate,
            ]);
        }

        foreach ($movements as $movement) {
            $qtyIn = 0;
            $qtyOut = 0;

            if (in_array($movement->type, ['in', 'initial', 'purchase'])) {
                $qtyIn = $movement->quantity;
            } elseif (in_array($movement->type, ['out', 'sale'])) {
                $qtyOut = $movement->quantity;
            } elseif ($movement->type === 'adjustment') {
                if ($movement->after_stock > $movement->before_stock) {
                    $qtyIn = $movement->quantity;
                } else {
                    $qtyOut = $movement->quantity;
                }
            }

            $runningBalance += ($qtyIn - $qtyOut);

            $ref = $this->getReferenceText($movement);

            $entries->push([
                'date' => $movement->created_at->format('Y-m-d H:i:s'),
                'ref' => $ref,
                'type' => $movement->type,
                'quantity_in' => $qtyIn,
                'quantity_out' => $qtyOut,
                'balance' => $runningBalance,
                'notes' => $movement->notes,
                'created_by' => $movement->creator?->name,
            ]);
        }

        // Calculate summary
        $summary = $this->calculateStockMovementSummary($product->id, $startDate, $endDate, null);

        return [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => $product->stock,
            ],
            'summary' => array_merge($summary, [
                'beginning_balance' => $beginningBalance,
                'ending_balance' => $runningBalance,
            ]),
            'entries' => $entries,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateStockMovementSummary(int $productId, ?string $startDate, ?string $endDate, ?string $types): array
    {
        $query = $this->stockMovementRepository->query()
            ->where('product_id', $productId);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        if ($types) {
            $query->whereIn('type', explode(',', $types));
        }

        $movements = $query->get();

        $totalIn = 0;
        $totalOut = 0;
        $adjustmentIn = 0;
        $adjustmentOut = 0;

        foreach ($movements as $movement) {
            if (in_array($movement->type, ['in', 'initial', 'purchase'])) {
                $totalIn += $movement->quantity;
            } elseif (in_array($movement->type, ['out', 'sale'])) {
                $totalOut += $movement->quantity;
            } elseif ($movement->type === 'adjustment') {
                if ($movement->after_stock > $movement->before_stock) {
                    $adjustmentIn += $movement->quantity;
                } else {
                    $adjustmentOut += $movement->quantity;
                }
            }
        }

        $netChange = $totalIn - $totalOut + $adjustmentIn - $adjustmentOut;

        return [
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'adjustment_in' => $adjustmentIn,
            'adjustment_out' => $adjustmentOut,
            'net_change' => $netChange,
            'total_movements' => $movements->count(),
        ];
    }

    private function getReferenceText(StockMovement $movement): string
    {
        if (! $movement->reference_type || ! $movement->reference_id) {
            return 'MANUAL';
        }

        $referenceType = class_basename($movement->reference_type);

        return match ($referenceType) {
            'Purchase' => 'INV-'.$movement->reference_id,
            'Sale' => 'SALE-'.$movement->reference_id,
            'Bundle' => 'BUNDLE-'.$movement->reference_id,
            'Product' => 'INITIAL',
            default => 'REF-'.$movement->reference_id,
        };
    }
}
