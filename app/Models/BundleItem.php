<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $bundle_id
 * @property int|null $product_id
 * @property int|null $product_variant_id
 * @property int $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class BundleItem extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'bundle_id',
        'product_id',
        'product_variant_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /** @return BelongsTo<Bundle, $this> */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
