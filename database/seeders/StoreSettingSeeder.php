<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

final class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::firstOrCreate(
            ['id' => 1],
            [
                'store_name'     => 'Minimarket Saya',
                'store_tagline'  => null,
                'store_address'  => null,
                'store_city'     => null,
                'store_province' => null,
                'store_postal_code' => null,
                'store_phone'    => null,
                'store_email'    => null,
                'store_logo'     => null,
                'currency_code'  => 'IDR',
                'currency_symbol'=> 'Rp',
                'tax_enabled'    => false,
                'tax_rate'       => 0.00,
                'receipt_footer' => 'Terima kasih telah berbelanja!',
            ]
        );
    }
}
