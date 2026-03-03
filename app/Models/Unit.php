<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $symbol
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['name', 'symbol'];

    protected function casts(): array
    {
        return [];
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
