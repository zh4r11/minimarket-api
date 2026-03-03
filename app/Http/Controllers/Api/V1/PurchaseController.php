<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StorePurchaseRequest;
use App\Http\Requests\Api\V1\UpdatePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class PurchaseController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $purchases = Purchase::query()
            ->with(['supplier', 'items.product'])
            ->paginate(15);

        return $this->success(PurchaseResource::collection($purchases)->toResponse($request)->getData(true));
    }

    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $purchase = DB::transaction(function () use ($request): Purchase {
            $validated = $request->validated();

            $purchase = Purchase::query()->create([
                'supplier_id' => $validated['supplier_id'] ?? null,
                'invoice_number' => $validated['invoice_number'],
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

    public function show(Purchase $purchase): JsonResponse
    {
        $purchase->load(['supplier', 'items.product']);

        return $this->success(new PurchaseResource($purchase));
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        $purchase->update($request->validated());

        return $this->success(new PurchaseResource($purchase));
    }

    public function destroy(Purchase $purchase): JsonResponse
    {
        $purchase->delete();

        return $this->noContent();
    }
}
