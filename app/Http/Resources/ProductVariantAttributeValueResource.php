<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ProductVariantAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductVariantAttributeValue
 */
final class ProductVariantAttributeValueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attribute_id' => $this->attribute_id,
            'value' => $this->value,
            'attribute' => $this->whenLoaded('attribute', fn () => new ProductVariantAttributeResource($this->attribute)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
