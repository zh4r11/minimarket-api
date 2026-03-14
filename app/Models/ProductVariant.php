<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * Product variant — stored in the products table with type='variant' and parent_id set.
 *
 * @property int $id
 * @property int $parent_id
 * @property string $type
 * @property int|null $category_id
 * @property int|null $brand_id
 * @property int|null $unit_id
 * @property string $sku
 * @property string $name
 * @property string|null $description
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
    protected $table = 'products';

    /** @var list<string> */
    protected $fillable = [
        'parent_id',
        'category_id',
        'brand_id',
        'unit_id',
        'sku',
        'name',
        'description',
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

    protected static function booted(): void
    {
        // Automatically scope all queries to variant rows only
        static::addGlobalScope('variant', fn (Builder $q) => $q->where('products.type', 'variant'));

        // Automatically set type when creating
        static::creating(function (self $model): void {
            $model->type = 'variant';
        });
    }

    /** @return BelongsTo<Product, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    /**
     * Alias kept for backwards compatibility with code that calls ->product.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->parent();
    }

    /** @return BelongsToMany<ProductVariantAttributeValue, $this> */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductVariantAttributeValue::class,
            'product_variant_values',
            'product_id',
            'attribute_value_id'
        );
    }

    /** @return MorphMany<ProductPhoto, $this> */
    public function photos(): MorphMany
    {
        return $this->morphMany(ProductPhoto::class, 'photoable')->orderBy('sort_order');
    }
}
