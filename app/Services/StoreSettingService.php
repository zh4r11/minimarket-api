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
        $setting = $this->storeSettingRepository->first();

        if ($setting === null) {
            /** @var StoreSetting */
            return $this->storeSettingRepository->create($data);
        }

        /** @var StoreSetting */
        return $this->storeSettingRepository->update($setting, $data);
    }

    public function uploadLogo(UploadedFile $file): ?StoreSetting
    {
        $setting = $this->storeSettingRepository->first();

        if ($setting === null) {
            return null;
        }

        if ($setting->store_logo) {
            Storage::disk('public')->delete($setting->store_logo);
        }

        $path = $file->store('store-logos', 'public');

        /** @var StoreSetting */
        return $this->storeSettingRepository->update($setting, ['store_logo' => $path]);
    }

    public function uploadQrCode(UploadedFile $file): ?StoreSetting
    {
        $setting = $this->storeSettingRepository->first();

        if ($setting === null) {
            return null;
        }

        if ($setting->payment_qr_code) {
            Storage::disk('public')->delete($setting->payment_qr_code);
        }

        $path = $file->store('payment-qrcodes', 'public');

        /** @var StoreSetting */
        return $this->storeSettingRepository->update($setting, ['payment_qr_code' => $path]);
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

    public function deleteQrCode(): ?StoreSetting
    {
        $setting = $this->storeSettingRepository->first();

        if ($setting === null) {
            return null;
        }

        if ($setting->payment_qr_code) {
            Storage::disk('public')->delete($setting->payment_qr_code);
        }

        /** @var StoreSetting */
        return $this->storeSettingRepository->update($setting, ['payment_qr_code' => null]);
    }

}

