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
    public function index(Request $request): JsonResponse
    {
        $units = Unit::query()->paginate(15);

        return $this->success(UnitResource::collection($units)->toResponse($request)->getData(true));
    }

    public function store(StoreUnitRequest $request): JsonResponse
    {
        $unit = Unit::query()->create($request->validated());

        return $this->created(new UnitResource($unit));
    }

    public function show(Unit $unit): JsonResponse
    {
        return $this->success(new UnitResource($unit));
    }

    public function update(UpdateUnitRequest $request, Unit $unit): JsonResponse
    {
        $unit->update($request->validated());

        return $this->success(new UnitResource($unit));
    }

    public function destroy(Unit $unit): JsonResponse
    {
        $unit->delete();

        return $this->noContent();
    }
}
