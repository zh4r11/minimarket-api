<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PurchaseItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $purchase_id
 * @property int $product_id
 * @property int $quantity
 * @property string $buy_price
 * @property string $subtotal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class PurchaseItem extends Model
{
    /** @use HasFactory<PurchaseItemFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['purchase_id', 'product_id', 'quantity', 'buy_price', 'subtotal'];

    protected function casts(): array
    {
        return [
            'buy_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    /** @return BelongsTo<Purchase, $this> */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
