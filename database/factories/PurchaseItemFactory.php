<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseItem>
 */
final class PurchaseItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_id' => null,
            'product_id' => null,
            'quantity' => 1,
            'buy_price' => 1000,
            'subtotal' => 1000,
        ];
    }
}
