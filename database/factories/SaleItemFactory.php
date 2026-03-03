<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
final class SaleItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => null,
            'product_id' => null,
            'quantity' => 1,
            'sell_price' => 1000,
            'discount' => 0,
            'subtotal' => 1000,
        ];
    }
}
