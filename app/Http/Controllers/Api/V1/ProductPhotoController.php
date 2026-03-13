<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ProductPhotoResource;
use App\Models\Product;
use App\Models\ProductPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class ProductPhotoController extends ApiController
{
    private const MAX_PHOTOS = 5;

    /**
     * Upload photos for a product.
     *
     * Uploads one or more photos for the specified product (max 5 total).
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['required', 'image', 'max:2048'],
        ]);

        $existingCount = $product->photos()->count();
        $newCount = count($request->file('photos', []));

        if ($existingCount + $newCount > self::MAX_PHOTOS) {
            return $this->error(
                "Produk hanya dapat memiliki maksimal ".self::MAX_PHOTOS." foto. Saat ini sudah ada {$existingCount} foto."
            );
        }

        $nextOrder = $existingCount;
        $isFirstBatch = $existingCount === 0;

        foreach ($request->file('photos') as $index => $file) {
            $path = $file->store('product-photos', 'public');
            $product->photos()->create([
                'path' => $path,
                'sort_order' => $nextOrder++,
                'is_main' => $isFirstBatch && $index === 0,
            ]);
        }

        $product->load('photos');

        return $this->success(ProductPhotoResource::collection($product->photos));
    }

    /**
     * Delete a product photo.
     *
     * Removes the specified photo from a product.
     */
    public function destroy(Product $product, ProductPhoto $photo): JsonResponse
    {
        if ($photo->photoable_type !== Product::class || $photo->photoable_id !== $product->id) {
            return $this->forbidden('Foto ini tidak milik produk yang dimaksud.');
        }

        $wasMain = $photo->is_main;

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        if ($wasMain) {
            $product->photos()->orderBy('sort_order')->first()?->update(['is_main' => true]);
        }

        return $this->noContent();
    }

    /**
     * Set main photo for a product.
     *
     * Marks the specified photo as the main photo, unsets all others.
     */
    public function setMain(Product $product, ProductPhoto $photo): JsonResponse
    {
        if ($photo->photoable_type !== Product::class || $photo->photoable_id !== $product->id) {
            return $this->forbidden('Foto ini tidak milik produk yang dimaksud.');
        }

        $product->photos()->update(['is_main' => false]);
        $photo->update(['is_main' => true]);

        $product->load('photos');

        return $this->success(ProductPhotoResource::collection($product->photos));
    }
}
