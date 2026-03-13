<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property string $sku
 * @property string $buy_price
 * @property string $sell_price
 * @property int $stock
 * @property int $min_stock
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'sku',
        'buy_price',
        'sell_price',
        'stock',
        'min_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'buy_price' => 'decimal:2',
            'sell_price' => 'decimal:2',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsToMany<ProductVariantAttributeValue, $this> */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductVariantAttributeValue::class,
            'product_variant_values',
            'variant_id',
            'attribute_value_id'
        );
    }

    /** @return MorphMany<ProductPhoto, $this> */
    public function photos(): MorphMany
    {
        return $this->morphMany(ProductPhoto::class, 'photoable')->orderBy('sort_order');
    }
}
