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
     *
     * @queryParam search string Search by name or description. Example: Indofood
     * @queryParam is_active boolean Filter by active status (true/false). Example: true
     * @queryParam per_page integer Number of items per page (max 100). Defaults to 15. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $brands = Brand::query()
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%"))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
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
