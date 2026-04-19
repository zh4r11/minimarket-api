<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StoreSetting;
use App\Repositories\Contracts\StoreSettingRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class StoreSettingService
{
    public function __construct(
        private readonly StoreSettingRepositoryInterface $storeSettingRepository,
    ) {}

    public function get(): ?StoreSetting
    {
        return $this->storeSettingRepository->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): StoreSetting
    {
        if (isset($data['store_logo']) && $data['store_logo'] instanceof UploadedFile) {
            $data['store_logo'] = $this->uploadLogo($data['store_logo']);
        }

        $setting = $this->storeSettingRepository->first();

        if ($setting === null) {
            /** @var StoreSetting */
            return $this->storeSettingRepository->create($data);
        }

        if (isset($data['store_logo']) && $setting->store_logo) {
            Storage::disk('public')->delete($setting->store_logo);
        }

        /** @var StoreSetting */
        return $this->storeSettingRepository->update($setting, $data);
    }

    public function deleteLogo(): ?StoreSetting
    {
        $setting = $this->storeSettingRepository->first();

        if ($setting === null) {
            return null;
        }

        if ($setting->store_logo) {
            Storage::disk('public')->delete($setting->store_logo);
        }

        /** @var StoreSetting */
        return $this->storeSettingRepository->update($setting, ['store_logo' => null]);
    }

    private function uploadLogo(UploadedFile $file): string
    {
        return $file->store('store-logos', 'public');
    }
}
