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
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search'    => 'nullable|string',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'per_page'  => 'nullable|integer|min:1|max:100',
            'page'      => 'nullable|integer|min:1',
        ]);

        $perPage = min($filters['per_page'] ?? 15, 100);

        $categories = Category::query()
            ->with(['parent', 'children'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('description', 'like', "%{$s}%"))
            ->when($filters['parent_id'] ?? null, fn ($q) => $q->where('parent_id', $filters['parent_id']))
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
        $category->load(['parent', 'children']);

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
