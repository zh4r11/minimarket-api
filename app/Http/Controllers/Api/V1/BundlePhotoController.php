<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ProductPhotoResource;
use App\Models\Bundle;
use App\Models\ProductPhoto;
use App\Services\ProductPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BundlePhotoController extends ApiController
{
    public function __construct(
        private readonly ProductPhotoService $productPhotoService,
    ) {}

    /**
     * Upload photos for a bundle.
     *
     * Uploads one or more photos for the specified bundle (max 5 total).
     */
    public function store(Request $request, Bundle $bundle): JsonResponse
    {
        $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['required', 'image', 'max:2048'],
        ]);

        $newCount = count($request->file('photos', []));

        if (! $this->productPhotoService->canUpload($bundle, $newCount)) {
            $existingCount = $this->productPhotoService->getExistingCount($bundle);
            $maxPhotos = $this->productPhotoService->getMaxPhotos($bundle);

            return $this->error(
                "Bundle hanya dapat memiliki maksimal {$maxPhotos} foto. Saat ini sudah ada {$existingCount} foto."
            );
        }

        $photos = $this->productPhotoService->upload($bundle, $request->file('photos'));

        return $this->success(ProductPhotoResource::collection($photos));
    }

    /**
     * Delete a bundle photo.
     *
     * Removes the specified photo from a bundle.
     */
    public function destroy(Bundle $bundle, ProductPhoto $photo): JsonResponse
    {
        if ($photo->photoable_type !== Bundle::class || $photo->photoable_id !== $bundle->id) {
            return $this->forbidden('Foto ini tidak milik bundle yang dimaksud.');
        }

        $wasMain = $photo->is_main;

        $this->productPhotoService->destroyPhoto($photo);

        if ($wasMain) {
            $this->productPhotoService->promoteNextMain($bundle);
        }

        return $this->noContent();
    }

    /**
     * Set main photo for a bundle.
     *
     * Marks the specified photo as the main photo, unsets all others.
     */
    public function setMain(Bundle $bundle, ProductPhoto $photo): JsonResponse
    {
        if ($photo->photoable_type !== Bundle::class || $photo->photoable_id !== $bundle->id) {
            return $this->forbidden('Foto ini tidak milik bundle yang dimaksud.');
        }

        $photos = $this->productPhotoService->setMainPhoto($bundle, $photo);

        return $this->success(ProductPhotoResource::collection($photos));
    }
}
