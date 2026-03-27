<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreUnitRequest;
use App\Http\Requests\Api\V1\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use App\Services\UnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UnitController extends ApiController
{
    public function __construct(
        private readonly UnitService $unitService,
    ) {}

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

        $units = $this->unitService->list($filters);

        return $this->success(UnitResource::collection($units)->toResponse($request)->getData(true));
    }

    /**
     * Create a unit.
     *
     * Stores a new unit of measure.
     */
    public function store(StoreUnitRequest $request): JsonResponse
    {
        $unit = $this->unitService->create($request->validated());

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
        $unit = $this->unitService->update($unit, $request->validated());

        return $this->success(new UnitResource($unit));
    }

    /**
     * Delete a unit.
     *
     * Permanently deletes the specified unit of measure.
     */
    public function destroy(Unit $unit): JsonResponse
    {
        $this->unitService->delete($unit);

        return $this->noContent();
    }
}
