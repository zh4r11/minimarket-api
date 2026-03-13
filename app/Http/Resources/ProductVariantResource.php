<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductVariant
 */
final class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'buy_price' => $this->buy_price,
            'sell_price' => $this->sell_price,
            'stock' => $this->stock,
            'min_stock' => $this->min_stock,
            'is_active' => $this->is_active,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            'attribute_values' => $this->whenLoaded('attributeValues', fn () => ProductVariantAttributeValueResource::collection($this->attributeValues)),
            'photos' => $this->whenLoaded('product', fn () => ProductPhotoResource::collection($this->product->photos)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
