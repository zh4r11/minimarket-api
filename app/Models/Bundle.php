<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
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

    /** @return HasMany<BundleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BundleItem::class);
    }
}
