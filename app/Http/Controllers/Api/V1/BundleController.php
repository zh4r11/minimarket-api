<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreBundleRequest;
use App\Http\Requests\Api\V1\UpdateBundleRequest;
use App\Http\Resources\BundleResource;
use App\Models\Bundle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BundleController extends ApiController
{
    /**
     * List bundles.
     *
     * Returns a paginated list of bundles with their items.
     *
     * @queryParam search string Search by SKU or name. Example: BUNDLE-001
     * @queryParam is_active boolean Filter by active status (true/false). Example: true
     * @queryParam per_page integer Number of items per page (max 100). Defaults to 15. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $bundles = Bundle::query()
            ->with(['items.product', 'items.productVariant'])
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('sku', 'like', "%{$request->search}%"))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->paginate($perPage);

        return $this->success(BundleResource::collection($bundles)->toResponse($request)->getData(true));
    }

    /**
     * Create a bundle.
     *
     * Stores a new bundle along with its items.
     */
    public function store(StoreBundleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        $bundle = Bundle::query()->create($data);
        $bundle->items()->createMany($items);
        $bundle->load(['items.product', 'items.productVariant']);

        return $this->created(new BundleResource($bundle));
    }

    /**
     * Get a bundle.
     *
     * Returns the details of a specific bundle including all items.
     */
    public function show(Bundle $bundle): JsonResponse
    {
        $bundle->load(['items.product', 'items.productVariant']);

        return $this->success(new BundleResource($bundle));
    }

    /**
     * Update a bundle.
     *
     * Updates the specified bundle. If items are provided, they replace all existing items.
     */
    public function update(UpdateBundleRequest $request, Bundle $bundle): JsonResponse
    {
        $data = $request->validated();
        $items = $data['items'] ?? null;
        unset($data['items']);

        $bundle->update($data);

        if ($items !== null) {
            $bundle->items()->delete();
            $bundle->items()->createMany($items);
        }

        $bundle->load(['items.product', 'items.productVariant']);

        return $this->success(new BundleResource($bundle));
    }

    /**
     * Delete a bundle.
     *
     * Permanently deletes the bundle and all its items.
     */
    public function destroy(Bundle $bundle): JsonResponse
    {
        $bundle->delete();

        return $this->noContent();
    }
}
