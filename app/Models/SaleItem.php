<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SaleItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sale_id
 * @property int $product_id
 * @property int|null $variant_id
 * @property int $quantity
 * @property string $sell_price
 * @property string $discount
 * @property string $subtotal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class SaleItem extends Model
{
    /** @use HasFactory<SaleItemFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['sale_id', 'product_id', 'variant_id', 'quantity', 'sell_price', 'discount', 'subtotal'];

    protected function casts(): array
    {
        return [
            'sell_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variant_id');
    }
}
