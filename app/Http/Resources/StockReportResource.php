<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
final class StockReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $stock = $this->stock;
        $minStock = $this->min_stock;

        $status = null;
        if ($stock === 0) {
            $status = 'Habis';
        } elseif ($stock <= $minStock) {
            $status = 'Rendah';
        }

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'type' => $this->type,
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'unit' => $this->whenLoaded('unit', fn () => $this->unit?->name),
            'stock_in' => (int) $this->stock_in,
            'stock_out' => (int) $this->stock_out,
            'current_stock' => $stock,
            'min_stock' => $minStock,
            'status' => $status,
        ];
    }
}
