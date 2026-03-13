<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductVariantAttribute;
use App\Models\ProductVariantAttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariantAttributeValue>
 */
final class ProductVariantAttributeValueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attribute_id' => ProductVariantAttribute::factory(),
            'value' => fake()->word(),
        ];
    }
}
