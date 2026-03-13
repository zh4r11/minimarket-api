<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BundleItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BundleItem
 */
final class BundleItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bundle_id' => $this->bundle_id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'quantity' => $this->quantity,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            'product_variant' => $this->whenLoaded('productVariant', fn () => new ProductVariantResource($this->productVariant)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
