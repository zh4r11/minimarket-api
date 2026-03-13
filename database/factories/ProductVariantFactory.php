<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
final class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $buyPrice = fake()->randomFloat(2, 1000, 50000);

        return [
            'product_id' => Product::factory(),
            'sku' => fake()->unique()->bothify('SKU-VAR-####'),
            'buy_price' => $buyPrice,
            'sell_price' => round($buyPrice * 1.2, 2),
            'stock' => fake()->numberBetween(0, 100),
            'min_stock' => 5,
            'is_active' => true,
        ];
    }
}
