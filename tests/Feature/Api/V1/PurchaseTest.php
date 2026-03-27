<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Purchases', function (): void {
    it('requires authentication', function (): void {
        $this->getJson('/api/v1/purchases')->assertStatus(401);
    });

    it('returns list of purchases', function (): void {
        $user = User::factory()->create();
        Purchase::factory()->count(3)->create();
        $this->actingAs($user)->getJson('/api/v1/purchases')
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'data' => ['data']]);
    });

    it('creates a purchase with items', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/purchases', [
            'purchase_date' => '2025-01-01',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'buy_price' => 10000,
                ],
            ],
        ])->assertStatus(201)
          ->assertJsonPath('success', true);
        $this->assertDatabaseCount('purchases', 1);
    });

    it('shows a purchase', function (): void {
        $user = User::factory()->create();
        $purchase = Purchase::factory()->create();
        $this->actingAs($user)->getJson("/api/v1/purchases/{$purchase->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $purchase->id);
    });

    it('updates a purchase', function (): void {
        $user = User::factory()->create();
        $purchase = Purchase::factory()->create();
        $this->actingAs($user)->putJson("/api/v1/purchases/{$purchase->id}", ['status' => 'confirmed'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');
    });

    it('deletes a purchase', function (): void {
        $user = User::factory()->create();
        $purchase = Purchase::factory()->create();
        $this->actingAs($user)->deleteJson("/api/v1/purchases/{$purchase->id}")
            ->assertStatus(204);
        $this->assertDatabaseMissing('purchases', ['id' => $purchase->id]);
    });

    it('validates required fields on store', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/purchases', [])
            ->assertStatus(422);
    });
});
