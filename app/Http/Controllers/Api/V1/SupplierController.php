<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreSupplierRequest;
use App\Http\Requests\Api\V1\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SupplierController extends ApiController
{
    /**
     * List suppliers.
     *
     * Returns a paginated list of suppliers. Supports filtering by keyword.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search'   => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page'     => 'nullable|integer|min:1',
        ]);

        $perPage = min($filters['per_page'] ?? 15, 100);

        $suppliers = Supplier::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")
                ->orWhere('city', 'like', "%{$s}%"))
            ->paginate($perPage);

        return $this->success(SupplierResource::collection($suppliers)->toResponse($request)->getData(true));
    }

    /**
     * Create a supplier.
     *
     * Stores a new supplier.
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::query()->create($request->validated());

        return $this->created(new SupplierResource($supplier));
    }

    /**
     * Get a supplier.
     *
     * Returns the details of a specific supplier by ID.
     */
    public function show(Supplier $supplier): JsonResponse
    {
        return $this->success(new SupplierResource($supplier));
    }

    /**
     * Update a supplier.
     *
     * Updates the specified supplier.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $supplier->update($request->validated());

        return $this->success(new SupplierResource($supplier));
    }

    /**
     * Delete a supplier.
     *
     * Permanently deletes the specified supplier.
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return $this->noContent();
    }
}
