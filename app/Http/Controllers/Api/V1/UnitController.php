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
     *
     * @queryParam search string Search by name or symbol. Example: kg
     * @queryParam per_page integer Number of items per page (max 100). Defaults to 15. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $units = Unit::query()
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('symbol', 'like', "%{$request->search}%"))
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
