<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Purchase
 */
final class PurchaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'purchase_date' => $this->purchase_date instanceof \Illuminate\Support\Carbon
                ? $this->purchase_date->toDateString()
                : $this->purchase_date,
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'status' => $this->status,
            'supplier' => $this->whenLoaded('supplier', fn () => new SupplierResource($this->supplier)),
            'items' => $this->whenLoaded('items', fn () => PurchaseItemResource::collection($this->items)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
