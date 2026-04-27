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
            'variant_id' => $this->variant_id,
            'quantity' => $this->quantity,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            'variant' => $this->whenLoaded('variant', fn () => $this->variant !== null ? new ProductResource($this->variant) : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
