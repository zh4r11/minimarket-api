<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\UpdateStoreSettingRequest;
use App\Http\Resources\StoreSettingResource;
use App\Services\StoreSettingService;
use Illuminate\Http\JsonResponse;

final class StoreSettingController extends ApiController
{
    public function __construct(
        private readonly StoreSettingService $storeSettingService,
    ) {}

    /**
     * Get store settings.
     *
     * Returns the current store settings including name, address, logo, and other configurations.
     */
    public function show(): JsonResponse
    {
        $setting = $this->storeSettingService->get();

        if ($setting === null) {
            return $this->success(null);
        }

        return $this->success(new StoreSettingResource($setting));
    }

    /**
     * Update store settings.
     *
     * Updates the store settings. Creates a new record if none exists.
     * Use multipart/form-data when uploading a logo image.
     */
    public function update(UpdateStoreSettingRequest $request): JsonResponse
    {
        $setting = $this->storeSettingService->update($request->validated());

        return $this->success(new StoreSettingResource($setting));
    }

    /**
     * Delete store logo.
     *
     * Removes the store logo from storage and clears the logo field.
     */
    public function deleteLogo(): JsonResponse
    {
        $setting = $this->storeSettingService->deleteLogo();

        if ($setting === null) {
            return $this->notFound('Pengaturan toko belum dikonfigurasi.');
        }

        return $this->success(new StoreSettingResource($setting));
    }
}
