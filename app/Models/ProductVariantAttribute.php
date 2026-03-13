<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductVariantAttributeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ProductVariantAttribute extends Model
{
    /** @use HasFactory<ProductVariantAttributeFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
    ];

    /** @return HasMany<ProductVariantAttributeValue, $this> */
    public function values(): HasMany
    {
        return $this->hasMany(ProductVariantAttributeValue::class, 'attribute_id');
    }
}
