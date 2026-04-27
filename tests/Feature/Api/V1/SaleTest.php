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
          ->assertJsonPath('success', true);
        $this->assertDatabaseCount('sales', 1);
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

    it('deducts bundle components and recalculates bundle stock when selling bundle', function (): void {
        $user = User::factory()->create();
        $componentA = Product::factory()->create(['stock' => 20]);
        $componentB = Product::factory()->create(['stock' => 12]);

        $bundleResponse = $this->actingAs($user)->postJson('/api/v1/bundles', [
            'sku' => 'BDL-SALE-001',
            'name' => 'Paket Jual',
            'sell_price' => 50000,
            'items' => [
                ['product_id' => $componentA->id, 'quantity' => 2],
                ['product_id' => $componentB->id, 'quantity' => 3],
            ],
        ])->assertStatus(201)
            ->assertJsonPath('data.stock', 4);

        $bundleId = (int) $bundleResponse->json('data.id');

        $this->actingAs($user)->postJson('/api/v1/sales', [
            'sale_date' => '2025-01-01',
            'paid_amount' => 100000,
            'items' => [
                [
                    'product_id' => $bundleId,
                    'quantity' => 2,
                    'sell_price' => 50000,
                ],
            ],
        ])->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('products', ['id' => $componentA->id, 'stock' => 16]);
        $this->assertDatabaseHas('products', ['id' => $componentB->id, 'stock' => 6]);
        $this->assertDatabaseHas('products', ['id' => $bundleId, 'stock' => 2]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $componentA->id,
            'type' => 'sale',
            'quantity' => -4,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $componentB->id,
            'type' => 'sale',
            'quantity' => -6,
        ]);
    });

    it('recalculates dependent bundle stock when selling component directly', function (): void {
        $user = User::factory()->create();
        $component = Product::factory()->create(['stock' => 10]);

        $bundleResponse = $this->actingAs($user)->postJson('/api/v1/bundles', [
            'sku' => 'BDL-SALE-002',
            'name' => 'Paket Komponen',
            'sell_price' => 25000,
            'items' => [
                ['product_id' => $component->id, 'quantity' => 2],
            ],
        ])->assertStatus(201)
            ->assertJsonPath('data.stock', 5);

        $bundleId = (int) $bundleResponse->json('data.id');

        $this->actingAs($user)->postJson('/api/v1/sales', [
            'sale_date' => '2025-01-01',
            'paid_amount' => 50000,
            'items' => [
                [
                    'product_id' => $component->id,
                    'quantity' => 4,
                    'sell_price' => 12000,
                ],
            ],
        ])->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('products', ['id' => $component->id, 'stock' => 6]);
        $this->assertDatabaseHas('products', ['id' => $bundleId, 'stock' => 3]);
    });

    it('deducts variant stock when selling bundle that contains variant components', function (): void {
        $user = User::factory()->create();

        // Create a parent product with a variant
        $parent = Product::factory()->create(['type' => 'parent', 'stock' => 0]);
        $variantResponse = $this->actingAs($user)->postJson('/api/v1/product-variants', [
            'parent_id' => $parent->id,
            'sku' => 'VAR-BUNDLE-001',
            'name' => $parent->name . ' - Merah',
            'buy_price' => 5000,
            'sell_price' => 7000,
            'stock' => 20,
            'min_stock' => 2,
        ])->assertStatus(201);

        $variantId = (int) $variantResponse->json('data.id');

        // Create a bundle whose component is the variant
        $bundleResponse = $this->actingAs($user)->postJson('/api/v1/bundles', [
            'sku' => 'BDL-VAR-001',
            'name' => 'Paket Varian',
            'sell_price' => 15000,
            'items' => [
                ['product_id' => $parent->id, 'variant_id' => $variantId, 'quantity' => 2],
            ],
        ])->assertStatus(201)
            ->assertJsonPath('data.stock', 10);

        $bundleId = (int) $bundleResponse->json('data.id');

        // Sell 1 bundle (should deduct 2 from variant stock)
        $this->actingAs($user)->postJson('/api/v1/sales', [
            'sale_date' => now()->toDateString(),
            'paid_amount' => 15000,
            'items' => [
                [
                    'product_id' => $bundleId,
                    'quantity' => 1,
                    'sell_price' => 15000,
                ],
            ],
        ])->assertStatus(201);

        // Variant stock should drop from 20 to 18
        $this->assertDatabaseHas('products', ['id' => $variantId, 'stock' => 18]);
        // Bundle stock should recalculate to 9
        $this->assertDatabaseHas('products', ['id' => $bundleId, 'stock' => 9]);
    });

    it('validates required fields on store', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/sales', [])
            ->assertStatus(422);
    });
});
