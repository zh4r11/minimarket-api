<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
final class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', fn () => new ProductResource($this->parent)),
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'photos' => $this->whenLoaded('photos', fn () => ProductPhotoResource::collection($this->photos)),
            'buy_price' => $this->buy_price,
            'sell_price' => $this->sell_price,
            'stock' => $this->stock,
            'min_stock' => $this->min_stock,
            'has_variants' => $this->type === 'parent',
            'is_active' => $this->is_active,
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'brand' => $this->whenLoaded('brand', fn () => new BrandResource($this->brand)),
            'unit' => $this->whenLoaded('unit', fn () => new UnitResource($this->unit)),
            'variants' => $this->whenLoaded('variants', fn () => ProductVariantResource::collection($this->variants)),
            'attribute_values' => $this->whenLoaded('attributeValues', fn () => ProductVariantAttributeValueResource::collection($this->attributeValues)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
