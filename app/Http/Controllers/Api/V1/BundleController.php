<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreBundleRequest;
use App\Http\Requests\Api\V1\UpdateBundleRequest;
use App\Http\Resources\BundleResource;
use App\Models\Bundle;
use App\Services\BundleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BundleController extends ApiController
{
    public function __construct(
        private readonly BundleService $bundleService,
    ) {}

    /**
     * List bundles.
     *
     * Returns a paginated list of bundles with their items.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search'    => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'per_page'  => 'nullable|integer|min:1|max:100',
            'page'      => 'nullable|integer|min:1',
        ]);

        $bundles = $this->bundleService->list($filters);

        return $this->success(BundleResource::collection($bundles)->toResponse($request)->getData(true));
    }

    /**
     * Create a bundle.
     *
     * Stores a new bundle along with its items.
     */
    public function store(StoreBundleRequest $request): JsonResponse
    {
        $bundle = $this->bundleService->create($request->validated());

        return $this->created(new BundleResource($bundle));
    }

    /**
     * Get a bundle.
     *
     * Returns the details of a specific bundle including all items.
     */
    public function show(Bundle $bundle): JsonResponse
    {
        $bundle = $this->bundleService->show($bundle);

        return $this->success(new BundleResource($bundle));
    }

    /**
     * Update a bundle.
     *
     * Updates the specified bundle. If items are provided, they replace all existing items.
     */
    public function update(UpdateBundleRequest $request, Bundle $bundle): JsonResponse
    {
        $bundle = $this->bundleService->update($bundle, $request->validated());

        return $this->success(new BundleResource($bundle));
    }

    /**
     * Delete a bundle.
     *
     * Permanently deletes the bundle and all its items.
     */
    public function destroy(Bundle $bundle): JsonResponse
    {
        $this->bundleService->delete($bundle);

        return $this->noContent();
    }
}
