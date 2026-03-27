<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreSaleRequest;
use App\Http\Requests\Api\V1\UpdateSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SaleController extends ApiController
{
    public function __construct(
        private readonly SaleService $saleService,
    ) {}

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

        $sales = $this->saleService->list($filters);

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
        $sale = $this->saleService->create($request->validated());

        return $this->created(new SaleResource($sale));
    }

    /**
     * Get a sale.
     *
     * Returns the details of a specific sale transaction including cashier and items.
     */
    public function show(Sale $sale): JsonResponse
    {
        $sale = $this->saleService->show($sale);

        return $this->success(new SaleResource($sale));
    }

    /**
     * Update a sale.
     *
     * Updates the header fields of the specified sale transaction.
     */
    public function update(UpdateSaleRequest $request, Sale $sale): JsonResponse
    {
        $sale = $this->saleService->update($sale, $request->validated());

        return $this->success(new SaleResource($sale));
    }

    /**
     * Delete a sale.
     *
     * Permanently deletes the specified sale transaction.
     */
    public function destroy(Sale $sale): JsonResponse
    {
        $this->saleService->delete($sale);

        return $this->noContent();
    }
}
