<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ProductPhoto;
use App\Repositories\Contracts\ProductPhotoRepositoryInterface;

/**
 * @extends BaseRepository<ProductPhoto>
 */
final class ProductPhotoRepository extends BaseRepository implements ProductPhotoRepositoryInterface
{
    public function __construct(ProductPhoto $model)
    {
        parent::__construct($model);
    }
}
