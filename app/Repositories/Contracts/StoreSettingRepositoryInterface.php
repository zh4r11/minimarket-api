<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\StoreSetting;

/**
 * @extends RepositoryInterface<StoreSetting>
 */
interface StoreSettingRepositoryInterface extends RepositoryInterface
{
    public function first(): ?StoreSetting;
}
