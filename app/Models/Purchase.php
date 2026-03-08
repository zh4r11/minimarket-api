<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $supplier_id
 * @property string $invoice_number
 * @property Carbon $purchase_date
 * @property string $total_amount
 * @property string|null $notes
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'supplier_id',
        'invoice_number',
        'purchase_date',
        'total_amount',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return HasMany<PurchaseItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
