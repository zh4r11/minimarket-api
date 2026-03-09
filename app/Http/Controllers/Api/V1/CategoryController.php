<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreCategoryRequest;
use App\Http\Requests\Api\V1\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class CategoryController extends ApiController
{
    /**
     * List categories.
     *
     * Returns a paginated list of categories. Supports filtering by keyword.
     *
     * @queryParam search string Search by name or description. Example: Minuman
     * @queryParam per_page integer Number of items per page (max 100). Defaults to 15. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $categories = Category::query()
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%"))
            ->paginate($perPage);

        return $this->success(CategoryResource::collection($categories)->toResponse($request)->getData(true));
    }

    /**
     * Create a category.
     *
     * Stores a new category. The slug is automatically generated from the name.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::query()->create([
            ...$request->validated(),
            'slug' => Str::slug($request->name),
        ]);

        return $this->created(new CategoryResource($category));
    }

    /**
     * Get a category.
     *
     * Returns the details of a specific category by ID.
     */
    public function show(Category $category): JsonResponse
    {
        return $this->success(new CategoryResource($category));
    }

    /**
     * Update a category.
     *
     * Updates the specified category. The slug is automatically regenerated if the name changes.
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return $this->success(new CategoryResource($category));
    }

    /**
     * Delete a category.
     *
     * Permanently deletes the specified category.
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return $this->noContent();
    }
}
