<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $store_name
 * @property string|null $store_tagline
 * @property string|null $store_address
 * @property string|null $store_city
 * @property string|null $store_province
 * @property string|null $store_postal_code
 * @property string|null $store_phone
 * @property string|null $store_email
 * @property string|null $store_logo
 * @property string|null $payment_qr_code
 * @property string $currency_code
 * @property string $currency_symbol
 * @property bool $tax_enabled
 * @property float $tax_rate
 * @property string|null $receipt_footer
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class StoreSetting extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'store_name',
        'store_tagline',
        'store_address',
        'store_city',
        'store_province',
        'store_postal_code',
        'store_phone',
        'store_email',
        'store_logo',
        'payment_qr_code',
        'currency_code',
        'currency_symbol',
        'tax_enabled',
        'tax_rate',
        'receipt_footer',
    ];

    protected function casts(): array
    {
        return [
            'tax_enabled' => 'boolean',
            'tax_rate'    => 'float',
        ];
    }
}
