<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $buyPrice = fake()->randomFloat(2, 1000, 50000);

        return [
            'category_id' => null,
            'unit_id' => null,
            'sku' => fake()->unique()->bothify('SKU-####'),
            'name' => fake()->words(3, true),
            'buy_price' => $buyPrice,
            'sell_price' => round($buyPrice * 1.2, 2),
            'stock' => fake()->numberBetween(0, 100),
            'min_stock' => 5,
            'is_active' => true,
        ];
    }
}
