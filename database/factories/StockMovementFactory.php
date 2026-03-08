<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
final class StockMovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => null,
            'type' => 'in',
            'quantity' => 10,
            'before_stock' => 0,
            'after_stock' => 10,
            'notes' => null,
            'created_by' => null,
        ];
    }
}
