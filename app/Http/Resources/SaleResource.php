<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Sale
 */
final class SaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'sale_date' => $this->sale_date instanceof \Illuminate\Support\Carbon
                ? $this->sale_date->toDateString()
                : $this->sale_date,
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'paid_amount' => $this->paid_amount,
            'change_amount' => $this->change_amount,
            'notes' => $this->notes,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'cashier' => $this->whenLoaded('cashier', fn () => new UserResource($this->cashier)),
            'items' => $this->whenLoaded('items', fn () => SaleItemResource::collection($this->items)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
