<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Stock Movements', function (): void {
    it('requires authentication', function (): void {
        $this->getJson('/api/v1/stock-movements')->assertStatus(401);
    });

    it('returns list of stock movements', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        StockMovement::factory()->create(['product_id' => $product->id]);
        $this->actingAs($user)->getJson('/api/v1/stock-movements')
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'data' => ['data']]);
    });

    it('creates a stock movement', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $this->actingAs($user)->postJson('/api/v1/stock-movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 5,
            'notes' => 'Test movement',
        ])->assertStatus(201)
          ->assertJsonPath('data.type', 'in')
          ->assertJsonPath('data.quantity', 5);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'in']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 15]);
    });

    it('shows a stock movement', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $movement = StockMovement::factory()->create(['product_id' => $product->id]);
        $this->actingAs($user)->getJson("/api/v1/stock-movements/{$movement->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $movement->id);
    });

    it('recalculates bundle stock and reflects it in products endpoint after stock movement', function (): void {
        $user = User::factory()->create();
        $component = Product::factory()->create(['stock' => 10]);

        $bundleResponse = $this->actingAs($user)->postJson('/api/v1/bundles', [
            'sku' => 'BDL-MOVE-001',
            'name' => 'Paket Mutasi',
            'sell_price' => 18000,
            'items' => [
                ['product_id' => $component->id, 'quantity' => 2],
            ],
        ])->assertStatus(201)
            ->assertJsonPath('data.stock', 5);

        $bundleId = (int) $bundleResponse->json('data.id');

        $this->actingAs($user)->postJson('/api/v1/stock-movements', [
            'product_id' => $component->id,
            'type' => 'out',
            'quantity' => 4,
            'notes' => 'Transfer keluar',
        ])->assertStatus(201)
            ->assertJsonPath('data.type', 'out')
            ->assertJsonPath('data.quantity', 4);

        $this->assertDatabaseHas('products', ['id' => $component->id, 'stock' => 6]);
        $this->assertDatabaseHas('products', ['id' => $bundleId, 'stock' => 3]);

        $productsResponse = $this->actingAs($user)
            ->getJson('/api/v1/products')
            ->assertStatus(200);

        $bundleProduct = collect($productsResponse->json('data.data'))
            ->firstWhere('id', $bundleId);

        expect($bundleProduct)->not->toBeNull();
        expect($bundleProduct['stock'])->toBe(3);
    });

    it('validates required fields on store', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/stock-movements', [])
            ->assertStatus(422);
    });
});
