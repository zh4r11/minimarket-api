<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $invoice_number
 * @property Carbon $sale_date
 * @property string $total_amount
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $paid_amount
 * @property string $change_amount
 * @property string|null $notes
 * @property int|null $cashier_id
 * @property string $payment_method
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'invoice_number',
        'sale_date',
        'total_amount',
        'discount_amount',
        'tax_amount',
        'paid_amount',
        'change_amount',
        'notes',
        'cashier_id',
        'payment_method',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'total_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
