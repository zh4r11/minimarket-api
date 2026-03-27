<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Bundle product — stored in the products table with type='bundle'.
 *
 * @property int $id
 * @property string $type
 * @property string $sku
 * @property string $name
 * @property string|null $description
 * @property string $sell_price
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Bundle extends Model
{
    protected $table = 'products';

    /** @var list<string> */
    protected $fillable = [
        'sku',
        'name',
        'description',
        'sell_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sell_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('bundle', fn (Builder $q) => $q->where('products.type', 'bundle'));

        static::creating(function (self $model): void {
            $model->type      = 'bundle';
            $model->buy_price = 0;
            $model->stock     = 0;
            $model->min_stock = 0;
        });
    }

    /** @return HasMany<BundleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'bundle_id');
    }
}
