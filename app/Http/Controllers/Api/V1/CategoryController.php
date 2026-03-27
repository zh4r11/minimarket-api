<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreCategoryRequest;
use App\Http\Requests\Api\V1\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CategoryController extends ApiController
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

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

        $categories = $this->categoryService->list($filters);

        return $this->success(CategoryResource::collection($categories)->toResponse($request)->getData(true));
    }

    /**
     * Create a category.
     *
     * Stores a new category. The slug is automatically generated from the name.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());

        return $this->created(new CategoryResource($category));
    }

    /**
     * Get a category.
     *
     * Returns the details of a specific category by ID.
     */
    public function show(Category $category): JsonResponse
    {
        $category = $this->categoryService->show($category);

        return $this->success(new CategoryResource($category));
    }

    /**
     * Update a category.
     *
     * Updates the specified category. The slug is automatically regenerated if the name changes.
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category = $this->categoryService->update($category, $request->validated());

        return $this->success(new CategoryResource($category));
    }

    /**
     * Delete a category.
     *
     * Permanently deletes the specified category.
     */
    public function destroy(Category $category): JsonResponse
    {
        $this->categoryService->delete($category);

        return $this->noContent();
    }
}
