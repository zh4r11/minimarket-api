<?php

declare(strict_types=1);

use App\Models\Bundle;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Products', function (): void {
    it('requires authentication', function (): void {
        $this->getJson('/api/v1/products')->assertStatus(401);
    });

    it('returns list of products', function (): void {
        $user = User::factory()->create();
        Product::factory()->count(3)->create();
        $this->actingAs($user)->getJson('/api/v1/products')
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'data' => ['data']]);
    });

    it('creates a product', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/products', [
            'sku' => 'SKU-001',
            'name' => 'Test Product',
            'buy_price' => 10000,
            'sell_price' => 15000,
        ])->assertStatus(201)
          ->assertJsonPath('data.sku', 'SKU-001')
          ->assertJsonPath('data.name', 'Test Product');
        $this->assertDatabaseHas('products', ['sku' => 'SKU-001']);
    });

    it('shows a product', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->actingAs($user)->getJson("/api/v1/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $product->id);
    });

    it('updates a product', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->actingAs($user)->putJson("/api/v1/products/{$product->id}", ['name' => 'Updated Product'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Product');
    });

    it('deletes a product', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->actingAs($user)->deleteJson("/api/v1/products/{$product->id}")
            ->assertStatus(204);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    });

    it('validates required fields on store', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/products', [])
            ->assertStatus(422);
    });

    it('returns bundle photos in product list and detail', function (): void {
        $user = User::factory()->create();

        $bundle = Bundle::query()->create([
            'sku'        => 'BDL-PTEST-001',
            'name'       => 'Paket Test',
            'sell_price' => 10000,
            'is_active'  => true,
        ]);

        ProductPhoto::query()->create([
            'photoable_type' => Bundle::class,
            'photoable_id'   => $bundle->id,
            'path'           => 'product-photos/test.jpg',
            'sort_order'     => 0,
            'is_main'        => true,
        ]);

        // list endpoint
        $this->actingAs($user)->getJson('/api/v1/products')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $bundle->id])
            ->assertJsonPath('data.data.0.photos.0.is_main', true);

        // detail endpoint
        $this->actingAs($user)->getJson("/api/v1/products/{$bundle->id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.photos')
            ->assertJsonPath('data.photos.0.is_main', true);
    });
});
