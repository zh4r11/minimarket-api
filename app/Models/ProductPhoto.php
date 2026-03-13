<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $photoable_type
 * @property int $photoable_id
 * @property string $path
 * @property int $sort_order
 * @property bool $is_main
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ProductPhoto extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'photoable_type',
        'photoable_id',
        'path',
        'sort_order',
        'is_main',
    ];

    /** @return MorphTo<Model, $this> */
    public function photoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_main' => 'boolean',
        ];
    }
}
