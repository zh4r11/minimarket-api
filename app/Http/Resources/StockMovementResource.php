<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockMovement
 */
final class StockMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $referenceText = null;
        if ($this->reference_type && $this->reference_id) {
            $referenceType = class_basename($this->reference_type);
            $referenceText = match ($referenceType) {
                'Purchase' => 'INV-'.$this->reference_id,
                'Sale' => 'SALE-'.$this->reference_id,
                'Bundle' => 'BUNDLE-'.$this->reference_id,
                'Product' => 'INITIAL',
                default => 'REF-'.$this->reference_id,
            };
        }

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            'type' => $this->type,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'reference_text' => $referenceText,
            'quantity' => $this->quantity,
            'before_stock' => $this->before_stock,
            'after_stock' => $this->after_stock,
            'notes' => $this->notes,
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
