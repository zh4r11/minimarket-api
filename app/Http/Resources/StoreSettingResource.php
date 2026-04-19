<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin StoreSetting
 */
final class StoreSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'store_name'       => $this->store_name,
            'store_tagline'    => $this->store_tagline,
            'store_address'    => $this->store_address,
            'store_city'       => $this->store_city,
            'store_province'   => $this->store_province,
            'store_postal_code'=> $this->store_postal_code,
            'store_phone'      => $this->store_phone,
            'store_email'      => $this->store_email,
            'store_logo'       => $this->store_logo
                ? Storage::disk('public')->url($this->store_logo)
                : null,
            'currency_code'    => $this->currency_code,
            'currency_symbol'  => $this->currency_symbol,
            'tax_enabled'      => $this->tax_enabled,
            'tax_rate'         => $this->tax_rate,
            'receipt_footer'   => $this->receipt_footer,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
