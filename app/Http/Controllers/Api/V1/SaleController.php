<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreSaleRequest;
use App\Http\Requests\Api\V1\UpdateSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\InvoiceNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SaleController extends ApiController
{
    /**
     * List sales.
     *
     * Returns a paginated list of sales transactions with cashier and items. Supports search and filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search'         => 'nullable|string',
            'status'         => 'nullable|string|in:draft,completed,cancelled',
            'payment_method' => 'nullable|string|in:cash,transfer,qris',
            'per_page'       => 'nullable|integer|min:1|max:100',
            'page'           => 'nullable|integer|min:1',
        ]);

        $perPage = min($filters['per_page'] ?? 15, 100);

        $sales = Sale::query()
            ->with(['cashier', 'items.product'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('invoice_number', 'like', "%{$s}%")
                ->orWhere('notes', 'like', "%{$s}%"))
            ->when($filters['status'] ?? null, fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['payment_method'] ?? null, fn ($q) => $q->where('payment_method', $filters['payment_method']))
            ->paginate($perPage);

        return $this->success(SaleResource::collection($sales)->toResponse($request)->getData(true));
    }

    /**
     * Create a sale.
     *
     * Stores a new sales transaction along with its items.
     * Total amount, change amount, and subtotals are calculated automatically.
     */
    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = DB::transaction(function () use ($request): Sale {
            $validated = $request->validated();

            $sale = Sale::query()->create([
                'invoice_number' => (new InvoiceNumberService)->generateSaleNumber(),
                'sale_date' => $validated['sale_date'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'paid_amount' => $validated['paid_amount'],
                'notes' => $validated['notes'] ?? null,
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'status' => $validated['status'] ?? 'draft',
                'cashier_id' => auth()->id(),
                'total_amount' => 0,
                'change_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $itemDiscount = $item['discount'] ?? 0;
                $subtotal = ($item['quantity'] * $item['sell_price']) - $itemDiscount;
                $totalAmount += $subtotal;

                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'sell_price' => $item['sell_price'],
                    'discount' => $itemDiscount,
                    'subtotal' => $subtotal,
                ]);
            }

            $discountAmount = $validated['discount_amount'] ?? 0;
            $taxAmount = $validated['tax_amount'] ?? 0;
            $paidAmount = $validated['paid_amount'];
            $changeAmount = $paidAmount - ($totalAmount + $taxAmount - $discountAmount);

            $sale->update([
                'total_amount' => $totalAmount,
                'change_amount' => $changeAmount,
            ]);

            $sale->load(['cashier', 'items.product']);

            return $sale;
        });

        return $this->created(new SaleResource($sale));
    }

    /**
     * Get a sale.
     *
     * Returns the details of a specific sale transaction including cashier and items.
     */
    public function show(Sale $sale): JsonResponse
    {
        $sale->load(['cashier', 'items.product']);

        return $this->success(new SaleResource($sale));
    }

    /**
     * Update a sale.
     *
     * Updates the header fields of the specified sale transaction.
     */
    public function update(UpdateSaleRequest $request, Sale $sale): JsonResponse
    {
        $sale->update($request->validated());

        return $this->success(new SaleResource($sale));
    }

    /**
     * Delete a sale.
     *
     * Permanently deletes the specified sale transaction.
     */
    public function destroy(Sale $sale): JsonResponse
    {
        $sale->delete();

        return $this->noContent();
    }
}
