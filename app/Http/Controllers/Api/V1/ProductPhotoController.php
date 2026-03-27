<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ProductPhotoResource;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Services\ProductPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductPhotoController extends ApiController
{
    public function __construct(
        private readonly ProductPhotoService $productPhotoService,
    ) {}

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

        $newCount = count($request->file('photos', []));

        if (! $this->productPhotoService->canUpload($product, $newCount)) {
            $existingCount = $this->productPhotoService->getExistingCount($product);
            $maxPhotos = $this->productPhotoService->getMaxPhotos($product);

            return $this->error(
                "Produk hanya dapat memiliki maksimal {$maxPhotos} foto. Saat ini sudah ada {$existingCount} foto."
            );
        }

        $photos = $this->productPhotoService->upload($product, $request->file('photos'));

        return $this->success(ProductPhotoResource::collection($photos));
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

        $this->productPhotoService->destroyPhoto($photo);

        if ($wasMain) {
            $this->productPhotoService->promoteNextMain($product);
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

        $photos = $this->productPhotoService->setMainPhoto($product, $photo);

        return $this->success(ProductPhotoResource::collection($photos));
    }
}
