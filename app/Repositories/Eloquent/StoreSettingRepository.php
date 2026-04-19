<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\StoreSetting;
use App\Repositories\Contracts\StoreSettingRepositoryInterface;

/**
 * @extends BaseRepository<StoreSetting>
 */
final class StoreSettingRepository extends BaseRepository implements StoreSettingRepositoryInterface
{
    public function __construct(StoreSetting $model)
    {
        parent::__construct($model);
    }

    public function first(): ?StoreSetting
    {
        /** @var StoreSetting|null */
        return $this->query()->first();
    }
}
