<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StorePurchaseRequest;
use App\Http\Requests\Api\V1\UpdatePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PurchaseController extends ApiController
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
    ) {}

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

        $purchases = $this->purchaseService->list($filters);

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
        $purchase = $this->purchaseService->create($request->validated());

        return $this->created(new PurchaseResource($purchase));
    }

    /**
     * Get a purchase.
     *
     * Returns the details of a specific purchase order including supplier and items.
     */
    public function show(Purchase $purchase): JsonResponse
    {
        $purchase = $this->purchaseService->show($purchase);

        return $this->success(new PurchaseResource($purchase));
    }

    /**
     * Update a purchase.
     *
     * Updates the header fields of the specified purchase order.
     */
    public function update(UpdatePurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        $purchase = $this->purchaseService->update($purchase, $request->validated());

        return $this->success(new PurchaseResource($purchase));
    }

    /**
     * Delete a purchase.
     *
     * Permanently deletes the specified purchase order.
     */
    public function destroy(Purchase $purchase): JsonResponse
    {
        $this->purchaseService->delete($purchase);

        return $this->noContent();
    }
}
