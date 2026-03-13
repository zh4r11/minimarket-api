<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ProductPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductPhoto
 */
final class ProductPhotoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
