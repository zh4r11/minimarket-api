<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StorePurchaseRequest;
use App\Http\Requests\Api\V1\UpdatePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\InvoiceNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class PurchaseController extends ApiController
{
    /**
     * List purchases.
     *
     * Returns a paginated list of purchase orders with supplier and items. Supports search and filtering.
     *
     * @queryParam search string Search by invoice number or notes. Example: INV-2025
     * @queryParam supplier_id integer Filter by supplier ID. Example: 2
     * @queryParam status string Filter by status (draft, confirmed, received). Example: confirmed
     * @queryParam per_page integer Number of items per page (max 100). Defaults to 15. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $purchases = Purchase::query()
            ->with(['supplier', 'items.product'])
            ->when($request->search, fn ($q) => $q->where('invoice_number', 'like', "%{$request->search}%")
                ->orWhere('notes', 'like', "%{$request->search}%"))
            ->when($request->supplier_id, fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->paginate($perPage);

        return $this->success(PurchaseResource::collection($purchases)->toResponse($request)->getData(true));
    }

    /**
     * Create a purchase.
     *
     * Stores a new purchase order along with its items.
     * The total amount is automatically calculated from the items.
     */
    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $purchase = DB::transaction(function () use ($request): Purchase {
            $validated = $request->validated();

            $purchase = Purchase::query()->create([
                'supplier_id' => $validated['supplier_id'] ?? null,
                'invoice_number' => (new InvoiceNumberService)->generatePurchaseNumber(),
                'purchase_date' => $validated['purchase_date'],
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['buy_price'];
                $totalAmount += $subtotal;

                PurchaseItem::query()->create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'buy_price' => $item['buy_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            $purchase->update(['total_amount' => $totalAmount]);
            $purchase->load(['supplier', 'items.product']);

            return $purchase;
        });

        return $this->created(new PurchaseResource($purchase));
    }

    /**
     * Get a purchase.
     *
     * Returns the details of a specific purchase order including supplier and items.
     */
    public function show(Purchase $purchase): JsonResponse
    {
        $purchase->load(['supplier', 'items.product']);

        return $this->success(new PurchaseResource($purchase));
    }

    /**
     * Update a purchase.
     *
     * Updates the header fields of the specified purchase order.
     */
    public function update(UpdatePurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        $purchase->update($request->validated());
        $purchase->load(['supplier', 'items.product']);

        return $this->success(new PurchaseResource($purchase));
    }

    /**
     * Delete a purchase.
     *
     * Permanently deletes the specified purchase order.
     */
    public function destroy(Purchase $purchase): JsonResponse
    {
        $purchase->delete();

        return $this->noContent();
    }
}
