<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPhoto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class ProductPhotoService
{
    private const MAX_PHOTOS = 5;

    /**
     * @param  array<int, UploadedFile>  $photos
     * @return Collection<int, ProductPhoto>
     */
    public function upload(Product $product, array $photos): Collection
    {
        $existingCount = $product->photos()->count();
        $nextOrder = $existingCount;
        $isFirstBatch = $existingCount === 0;

        foreach ($photos as $index => $file) {
            $path = $file->store('product-photos', 'public');
            $product->photos()->create([
                'path' => $path,
                'sort_order' => $nextOrder++,
                'is_main' => $isFirstBatch && $index === 0,
            ]);
        }

        $product->load('photos');

        return $product->photos;
    }

    public function canUpload(Model $owner, int $newCount): bool
    {
        $maxPhotos = $owner instanceof Product ? self::MAX_PHOTOS : 2;

        return $owner->photos()->count() + $newCount <= $maxPhotos;
    }

    public function getExistingCount(Model $owner): int
    {
        return $owner->photos()->count();
    }

    public function getMaxPhotos(Model $owner): int
    {
        return $owner instanceof Product ? self::MAX_PHOTOS : 2;
    }

    public function destroyPhoto(ProductPhoto $photo): bool
    {
        Storage::disk('public')->delete($photo->path);

        return (bool) $photo->delete();
    }

    /**
     * @return Collection<int, ProductPhoto>
     */
    public function setMainPhoto(Model $owner, ProductPhoto $photo): Collection
    {
        $owner->photos()->update(['is_main' => false]);
        $photo->update(['is_main' => true]);

        $owner->load('photos');

        return $owner->photos;
    }

    public function promoteNextMain(Model $owner): void
    {
        $owner->photos()->orderBy('sort_order')->first()?->update(['is_main' => true]);
    }
}
