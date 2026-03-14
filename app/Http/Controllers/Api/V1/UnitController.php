<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreUnitRequest;
use App\Http\Requests\Api\V1\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UnitController extends ApiController
{
    /**
     * List units.
     *
     * Returns a paginated list of units of measure. Supports filtering by keyword.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search'   => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page'     => 'nullable|integer|min:1',
        ]);

        $perPage = min($filters['per_page'] ?? 15, 100);

        $units = Unit::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('symbol', 'like', "%{$s}%"))
            ->paginate($perPage);

        return $this->success(UnitResource::collection($units)->toResponse($request)->getData(true));
    }

    /**
     * Create a unit.
     *
     * Stores a new unit of measure.
     */
    public function store(StoreUnitRequest $request): JsonResponse
    {
        $unit = Unit::query()->create($request->validated());

        return $this->created(new UnitResource($unit));
    }

    /**
     * Get a unit.
     *
     * Returns the details of a specific unit of measure by ID.
     */
    public function show(Unit $unit): JsonResponse
    {
        return $this->success(new UnitResource($unit));
    }

    /**
     * Update a unit.
     *
     * Updates the specified unit of measure.
     */
    public function update(UpdateUnitRequest $request, Unit $unit): JsonResponse
    {
        $unit->update($request->validated());

        return $this->success(new UnitResource($unit));
    }

    /**
     * Delete a unit.
     *
     * Permanently deletes the specified unit of measure.
     */
    public function destroy(Unit $unit): JsonResponse
    {
        $unit->delete();

        return $this->noContent();
    }
}
