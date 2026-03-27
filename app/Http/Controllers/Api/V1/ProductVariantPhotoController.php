<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ProductPhotoResource;
use App\Models\ProductPhoto;
use App\Models\ProductVariant;
use App\Services\ProductPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductVariantPhotoController extends ApiController
{
    public function __construct(
        private readonly ProductPhotoService $productPhotoService,
    ) {}

    /**
     * Upload photos for a product variant.
     *
     * Uploads one or more photos for the specified variant (max 2 total).
     */
    public function store(Request $request, ProductVariant $productVariant): JsonResponse
    {
        $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['required', 'image', 'max:2048'],
        ]);

        $newCount = count($request->file('photos', []));

        if (! $this->productPhotoService->canUpload($productVariant, $newCount)) {
            $existingCount = $this->productPhotoService->getExistingCount($productVariant);
            $maxPhotos = $this->productPhotoService->getMaxPhotos($productVariant);

            return $this->error(
                "Varian hanya dapat memiliki maksimal {$maxPhotos} foto. Saat ini sudah ada {$existingCount} foto."
            );
        }

        $photos = $this->productPhotoService->upload($productVariant, $request->file('photos'));

        return $this->success(ProductPhotoResource::collection($photos));
    }

    /**
     * Delete a product variant photo.
     *
     * Removes the specified photo from a product variant.
     */
    public function destroy(ProductVariant $productVariant, ProductPhoto $photo): JsonResponse
    {
        if ($photo->photoable_type !== ProductVariant::class || $photo->photoable_id !== $productVariant->id) {
            return $this->forbidden('Foto ini tidak milik varian yang dimaksud.');
        }

        $wasMain = $photo->is_main;

        $this->productPhotoService->destroyPhoto($photo);

        if ($wasMain) {
            $this->productPhotoService->promoteNextMain($productVariant);
        }

        return $this->noContent();
    }

    /**
     * Set main photo for a product variant.
     *
     * Marks the specified photo as the main photo, unsets all others.
     */
    public function setMain(ProductVariant $productVariant, ProductPhoto $photo): JsonResponse
    {
        if ($photo->photoable_type !== ProductVariant::class || $photo->photoable_id !== $productVariant->id) {
            return $this->forbidden('Foto ini tidak milik varian yang dimaksud.');
        }

        $photos = $this->productPhotoService->setMainPhoto($productVariant, $photo);

        return $this->success(ProductPhotoResource::collection($photos));
    }
}
