<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
final class SaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => fake()->unique()->bothify('INV-####'),
            'sale_date' => today()->toDateString(),
            'total_amount' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'paid_amount' => 0,
            'change_amount' => 0,
            'notes' => null,
            'cashier_id' => null,
            'payment_method' => 'cash',
            'status' => 'draft',
        ];
    }
}
