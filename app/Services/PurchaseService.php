<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Purchase;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PurchaseService
{
    public function __construct(
        private readonly PurchaseRepositoryInterface $purchaseRepository,
        private readonly InvoiceNumberService $invoiceNumberService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $this->purchaseRepository->paginate($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data): Purchase {
            /** @var Purchase $purchase */
            $purchase = $this->purchaseRepository->create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'invoice_number' => $this->invoiceNumberService->generatePurchaseNumber(),
                'purchase_date' => $data['purchase_date'],
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($data['items'] as $item) {
                $subtotal = $item['quantity'] * $item['buy_price'];
                $totalAmount += $subtotal;

                $this->purchaseRepository->createItem($purchase->id, [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'buy_price' => $item['buy_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            $this->purchaseRepository->update($purchase, ['total_amount' => $totalAmount]);
            $purchase->load(['supplier', 'items.product']);

            return $purchase;
        });
    }

    public function show(Purchase $purchase): Purchase
    {
        $purchase->load(['supplier', 'items.product']);

        return $purchase;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Purchase $purchase, array $data): Purchase
    {
        $this->purchaseRepository->update($purchase, $data);
        $purchase->load(['supplier', 'items.product']);

        return $purchase;
    }

    public function delete(Purchase $purchase): void
    {
        $this->purchaseRepository->delete($purchase);
    }
}
