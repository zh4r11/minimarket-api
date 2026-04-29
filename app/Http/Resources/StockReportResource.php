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
            $status = 'empty';
        } elseif ($stock <= $minStock) {
            $status = 'low';
        } elseif ($stock > $minStock * 2) {
            $status = 'over';
        } else {
            $status = 'normal';
        }

        $stockIn = (int) ($this->stock_in ?? 0);
        $stockOut = (int) ($this->stock_out ?? 0);
        $stockAdjustment = (int) ($this->stock_adjustment ?? 0);

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'type' => $this->type,
            'parent_id' => $this->parent_id,
            'parent_name' => $this->whenLoaded('parent', fn () => $this->parent?->name),
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'unit' => $this->whenLoaded('unit', fn () => $this->unit?->name),
            'stock_in' => $stockIn,
            'stock_out' => $stockOut,
            'stock_adjustment' => $stockAdjustment,
            'net_change' => $stockIn - $stockOut,
            'current_stock' => $stock,
            'min_stock' => $minStock,
            'status' => $status,
            'buy_price' => (float) $this->buy_price,
            'sell_price' => (float) $this->sell_price,
            'stock_value' => (float) $stock * (float) $this->buy_price,
        ];
    }
}
