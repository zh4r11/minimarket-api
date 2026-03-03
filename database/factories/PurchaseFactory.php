<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
final class PurchaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => null,
            'invoice_number' => fake()->unique()->bothify('PO-####'),
            'purchase_date' => today()->toDateString(),
            'total_amount' => 0,
            'notes' => null,
            'status' => 'draft',
        ];
    }
}
