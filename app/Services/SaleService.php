<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BundleItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SaleRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class SaleService
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StockMovementRepositoryInterface $stockMovementRepository,
        private readonly InvoiceNumberService $invoiceNumberService,
        private readonly BundleStockService $bundleStockService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $this->saleRepository->paginate($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data): Sale {
            /** @var Sale $sale */
            $sale = $this->saleRepository->create([
                'invoice_number' => $this->invoiceNumberService->generateSaleNumber(),
                'sale_date' => $data['sale_date'],
                'discount_amount' => $data['discount_amount'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'paid_amount' => $data['paid_amount'],
                'notes' => $data['notes'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'status' => $data['status'] ?? 'draft',
                'cashier_id' => auth()->id(),
                'total_amount' => 0,
                'change_amount' => 0,
            ]);

            $totalAmount = 0;
            $affectedComponentProductIds = [];

            foreach ($data['items'] as $item) {
                // Support bundle_id as an alias for product_id
                if (isset($item['bundle_id']) && ! isset($item['product_id'])) {
                    $item['product_id'] = $item['bundle_id'];
                }

                $product = Product::query()->withoutGlobalScopes()->findOrFail($item['product_id']);
                $itemDiscount = $item['discount'] ?? 0;
                $subtotal = ($item['quantity'] * $item['sell_price']) - $itemDiscount;
                $totalAmount += $subtotal;

                $this->saleRepository->createItem($sale->id, [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'sell_price' => $item['sell_price'],
                    'discount' => $itemDiscount,
                    'subtotal' => $subtotal,
                ]);

                $this->deductStock($product, $item, $sale, $affectedComponentProductIds);
            }

            $this->bundleStockService->recalculateAffectedBundlesByComponentIds($affectedComponentProductIds);

            $discountAmount = $data['discount_amount'] ?? 0;
            $taxAmount = $data['tax_amount'] ?? 0;
            $paidAmount = $data['paid_amount'];
            $changeAmount = $paidAmount - ($totalAmount + $taxAmount - $discountAmount);

            $this->saleRepository->update($sale, [
                'total_amount' => $totalAmount,
                'change_amount' => $changeAmount,
            ]);

            $sale->load(['cashier', 'items.product']);

            return $sale;
        });
    }

    public function show(Sale $sale): Sale
    {
        $sale->load(['cashier', 'items.product']);

        return $sale;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Sale $sale, array $data): Sale
    {
        /** @var Sale */
        return $this->saleRepository->update($sale, $data);
    }

    public function delete(Sale $sale): void
    {
        $this->saleRepository->delete($sale);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, int>  $affectedComponentProductIds
     */
    private function deductStock(Product $product, array $item, Sale $sale, array &$affectedComponentProductIds): void
    {
        if ($product->type === 'bundle') {
            $this->deductBundleStock($product, $item, $sale, $affectedComponentProductIds);
        } else {
            $this->deductProductStock($product, $item, $sale, $affectedComponentProductIds);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, int>  $affectedComponentProductIds
     */
    private function deductBundleStock(Product $product, array $item, Sale $sale, array &$affectedComponentProductIds): void
    {
        $bundleItems = BundleItem::query()
            ->with(['product', 'variant'])
            ->where('bundle_id', $product->id)
            ->get();

        foreach ($bundleItems as $bundleItem) {
            // Prefer explicit variant; fall back to product (which may itself be a variant row)
            $component = $bundleItem->variant ?? $bundleItem->product;

            if ($component === null) {
                continue;
            }

            $deductQty = $item['quantity'] * $bundleItem->quantity;
            $beforeStock = $component->stock;
            $afterStock = $beforeStock - $deductQty;

            $component->decrement('stock', $deductQty);
            $affectedComponentProductIds[] = $component->id;

            $this->stockMovementRepository->create([
                'product_id' => $component->id,
                'type' => 'out',
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'quantity' => $deductQty,
                'before_stock' => $beforeStock,
                'after_stock' => $afterStock,
                'notes' => "Bundle: {$product->name}",
                'created_by' => auth()->id(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, int>  $affectedComponentProductIds
     */
    private function deductProductStock(Product $product, array $item, Sale $sale, array &$affectedComponentProductIds): void
    {
        if (isset($item['variant_id'])) {
            /** @var ProductVariant $variant */
            $variant = ProductVariant::query()->findOrFail($item['variant_id']);
            $beforeStock = $variant->stock;
            $afterStock = $beforeStock - $item['quantity'];

            $variant->decrement('stock', $item['quantity']);
            $affectedComponentProductIds[] = $variant->id;

            $this->stockMovementRepository->create([
                'product_id' => $variant->id,
                'type' => 'out',
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'quantity' => $item['quantity'],
                'before_stock' => $beforeStock,
                'after_stock' => $afterStock,
                'notes' => null,
                'created_by' => auth()->id(),
            ]);

            return;
        }

        $beforeStock = $product->stock;
        $afterStock = $beforeStock - $item['quantity'];

        $product->decrement('stock', $item['quantity']);
        $affectedComponentProductIds[] = $product->id;

        $this->stockMovementRepository->create([
            'product_id' => $product->id,
            'type' => 'out',
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'quantity' => $item['quantity'],
            'before_stock' => $beforeStock,
            'after_stock' => $afterStock,
            'notes' => null,
            'created_by' => auth()->id(),
        ]);
    }
}
