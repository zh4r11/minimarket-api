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
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search'      => 'nullable|string',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'status'      => 'nullable|string|in:draft,confirmed,received',
            'per_page'    => 'nullable|integer|min:1|max:100',
            'page'        => 'nullable|integer|min:1',
        ]);

        $perPage = min($filters['per_page'] ?? 15, 100);

        $purchases = Purchase::query()
            ->with(['supplier', 'items.product'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('invoice_number', 'like', "%{$s}%")
                ->orWhere('notes', 'like', "%{$s}%"))
            ->when($filters['supplier_id'] ?? null, fn ($q) => $q->where('supplier_id', $filters['supplier_id']))
            ->when($filters['status'] ?? null, fn ($q) => $q->where('status', $filters['status']))
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
