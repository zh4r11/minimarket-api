<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with(['category', 'unit'])
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->paginate(15);

        return $this->success(ProductResource::collection($products)->toResponse($request)->getData(true));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::query()->create($request->validated());
        $product->load(['category', 'unit']);

        return $this->created(new ProductResource($product));
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'unit']);

        return $this->success(new ProductResource($product));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());
        $product->load(['category', 'unit']);

        return $this->success(new ProductResource($product));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return $this->noContent();
    }
}
