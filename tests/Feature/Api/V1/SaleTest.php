<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Sales', function (): void {
    it('requires authentication', function (): void {
        $this->getJson('/api/v1/sales')->assertStatus(401);
    });

    it('returns list of sales', function (): void {
        $user = User::factory()->create();
        Sale::factory()->count(3)->create();
        $this->actingAs($user)->getJson('/api/v1/sales')
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'data' => ['data']]);
    });

    it('creates a sale with items', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/sales', [
            'invoice_number' => 'INV-0001',
            'sale_date' => '2025-01-01',
            'paid_amount' => 20000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'sell_price' => 10000,
                ],
            ],
        ])->assertStatus(201)
          ->assertJsonPath('data.invoice_number', 'INV-0001');
        $this->assertDatabaseHas('sales', ['invoice_number' => 'INV-0001']);
    });

    it('shows a sale', function (): void {
        $user = User::factory()->create();
        $sale = Sale::factory()->create();
        $this->actingAs($user)->getJson("/api/v1/sales/{$sale->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $sale->id);
    });

    it('updates a sale', function (): void {
        $user = User::factory()->create();
        $sale = Sale::factory()->create();
        $this->actingAs($user)->putJson("/api/v1/sales/{$sale->id}", ['status' => 'completed'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    });

    it('deletes a sale', function (): void {
        $user = User::factory()->create();
        $sale = Sale::factory()->create();
        $this->actingAs($user)->deleteJson("/api/v1/sales/{$sale->id}")
            ->assertStatus(204);
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    });

    it('validates required fields on store', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/sales', [])
            ->assertStatus(422);
    });
});
