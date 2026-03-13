<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ProductPhotoResource;
use App\Models\ProductPhoto;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class ProductVariantPhotoController extends ApiController
{
    private const MAX_PHOTOS = 2;

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

        $existingCount = $productVariant->photos()->count();
        $newCount = count($request->file('photos', []));

        if ($existingCount + $newCount > self::MAX_PHOTOS) {
            return $this->error(
                "Varian hanya dapat memiliki maksimal ".self::MAX_PHOTOS." foto. Saat ini sudah ada {$existingCount} foto."
            );
        }

        $nextOrder = $existingCount;

        foreach ($request->file('photos') as $file) {
            $path = $file->store('product-variant-photos', 'public');
            $productVariant->photos()->create([
                'path' => $path,
                'sort_order' => $nextOrder++,
            ]);
        }

        $productVariant->load('photos');

        return $this->success(ProductPhotoResource::collection($productVariant->photos));
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

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return $this->noContent();
    }
}
