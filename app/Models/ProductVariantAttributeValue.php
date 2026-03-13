<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductVariantAttributeValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\ProductVariant;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $attribute_id
 * @property string $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ProductVariantAttributeValue extends Model
{
    /** @use HasFactory<ProductVariantAttributeValueFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'attribute_id',
        'value',
    ];

    /** @return BelongsTo<ProductVariantAttribute, $this> */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductVariantAttribute::class, 'attribute_id');
    }

    /** @return BelongsToMany<ProductVariant, $this> */
    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_variant_values',
            'attribute_value_id',
            'variant_id'
        );
    }
}
