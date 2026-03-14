<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreBrandRequest;
use App\Http\Requests\Api\V1\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class BrandController extends ApiController
{
    /**
     * List brands.
     *
     * Returns a paginated list of brands. Supports filtering by keyword and active status.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search'    => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'per_page'  => 'nullable|integer|min:1|max:100',
            'page'      => 'nullable|integer|min:1',
        ]);

        $perPage = min($filters['per_page'] ?? 15, 100);

        $brands = Brand::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('description', 'like', "%{$s}%"))
            ->when(array_key_exists('is_active', $filters), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->paginate($perPage);

        return $this->success(BrandResource::collection($brands)->toResponse($request)->getData(true));
    }

    /**
     * Create a brand.
     *
     * Stores a new brand. The slug is automatically generated from the name.
     */
    public function store(StoreBrandRequest $request): JsonResponse
    {
        $brand = Brand::query()->create([
            ...$request->validated(),
            'slug' => Str::slug($request->name),
        ]);

        return $this->created(new BrandResource($brand));
    }

    /**
     * Get a brand.
     *
     * Returns the details of a specific brand by ID.
     */
    public function show(Brand $brand): JsonResponse
    {
        return $this->success(new BrandResource($brand));
    }

    /**
     * Update a brand.
     *
     * Updates the specified brand. The slug is automatically regenerated if the name changes.
     */
    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $brand->update($data);

        return $this->success(new BrandResource($brand));
    }

    /**
     * Delete a brand.
     *
     * Permanently deletes the specified brand.
     */
    public function destroy(Brand $brand): JsonResponse
    {
        $brand->delete();

        return $this->noContent();
    }
}
