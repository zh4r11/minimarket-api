<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin array<string, mixed>
 */
final class StockSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'total_in' => $this['total_in'] ?? 0,
            'total_out' => $this['total_out'] ?? 0,
            'adjustment_in' => $this['adjustment_in'] ?? 0,
            'adjustment_out' => $this['adjustment_out'] ?? 0,
            'net_change' => $this['net_change'] ?? 0,
            'total_movements' => $this['total_movements'] ?? 0,
            'beginning_balance' => $this['beginning_balance'] ?? null,
            'ending_balance' => $this['ending_balance'] ?? null,
        ];
    }
}
