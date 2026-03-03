<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PurchaseItem
 */
final class PurchaseItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            'quantity' => $this->quantity,
            'buy_price' => $this->buy_price,
            'subtotal' => $this->subtotal,
        ];
    }
}
