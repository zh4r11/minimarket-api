<?php

declare(strict_types=1);

use App\Models\Bundle;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

describe('Bundles', function (): void {
    it('requires authentication', function (): void {
        $this->getJson('/api/v1/bundles')->assertStatus(401);
    });

    it('calculates bundle stock on create from component stock', function (): void {
        $user = User::factory()->create();
        $componentA = Product::factory()->create(['stock' => 10]);
        $componentB = Product::factory()->create(['stock' => 9]);

        $response = $this->actingAs($user)->postJson('/api/v1/bundles', [
            'sku' => 'BDL-CREATE-001',
            'name' => 'Paket Sarapan',
            'sell_price' => 20000,
            'items' => [
                ['product_id' => $componentA->id, 'quantity' => 2],
                ['product_id' => $componentB->id, 'quantity' => 3],
            ],
        ])->assertStatus(201)
            ->assertJsonPath('data.stock', 3);

        $bundleId = (int) $response->json('data.id');

        $this->assertDatabaseHas('products', [
            'id' => $bundleId,
            'type' => 'bundle',
            'stock' => 3,
        ]);
    });

    it('uses variant stock when item includes variant_id', function (): void {
        $user = User::factory()->create();

        $parentProduct = Product::factory()->create([
            'type' => 'parent',
            'stock' => 999,
        ]);

        $variantProduct = Product::factory()->create([
            'type' => 'variant',
            'parent_id' => $parentProduct->id,
            'stock' => 4,
        ]);

        $component = Product::factory()->create(['stock' => 20]);

        $response = $this->actingAs($user)->postJson('/api/v1/bundles', [
            'sku' => 'BDL-VARIANT-001',
            'name' => 'Paket Variant',
            'sell_price' => 30000,
            'items' => [
                [
                    'product_id' => $parentProduct->id,
                    'variant_id' => $variantProduct->id,
                    'quantity' => 2,
                ],
                [
                    'product_id' => $component->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertStatus(201)
            ->assertJsonPath('data.stock', 2);

        $bundleId = (int) $response->json('data.id');

        $this->assertDatabaseHas('bundle_items', [
            'bundle_id' => $bundleId,
            'product_id' => $parentProduct->id,
            'variant_id' => $variantProduct->id,
            'quantity' => 2,
        ]);

        $variantIds = collect($response->json('data.items'))
            ->pluck('variant_id')
            ->filter()
            ->values()
            ->all();

        expect($variantIds)->toContain($variantProduct->id);
    });

    it('recalculates bundle stock on update and returns stock in index and detail', function (): void {
        $user = User::factory()->create();
        $componentA = Product::factory()->create(['stock' => 12]);
        $componentB = Product::factory()->create(['stock' => 10]);

        $createResponse = $this->actingAs($user)->postJson('/api/v1/bundles', [
            'sku' => 'BDL-UPDATE-001',
            'name' => 'Paket Update',
            'sell_price' => 25000,
            'items' => [
                ['product_id' => $componentA->id, 'quantity' => 2],
                ['product_id' => $componentB->id, 'quantity' => 2],
            ],
        ])->assertStatus(201)
            ->assertJsonPath('data.stock', 5);

        $bundleId = (int) $createResponse->json('data.id');

        $this->actingAs($user)->putJson("/api/v1/bundles/{$bundleId}", [
            'items' => [
                ['product_id' => $componentA->id, 'quantity' => 3],
                ['product_id' => $componentB->id, 'quantity' => 2],
            ],
        ])->assertStatus(200)
            ->assertJsonPath('data.stock', 4);

        $this->actingAs($user)
            ->getJson("/api/v1/bundles/{$bundleId}")
            ->assertStatus(200)
            ->assertJsonPath('data.stock', 4);

        $indexResponse = $this->actingAs($user)
            ->getJson('/api/v1/bundles')
            ->assertStatus(200);

        $bundleFromIndex = collect($indexResponse->json('data.data'))
            ->firstWhere('id', $bundleId);

        expect($bundleFromIndex)->not->toBeNull();
        expect($bundleFromIndex['stock'])->toBe(4);
    });

    it('uploads bundle photos and returns them in bundle detail', function (): void {
        Storage::fake('public');

        $user = User::factory()->create();
        $bundle = Bundle::query()->create([
            'sku' => 'BDL-PHOTO-001',
            'name' => 'Paket Foto',
            'sell_price' => 30000,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(
            "/api/v1/bundles/{$bundle->id}/photos",
            [
                'photos' => [UploadedFile::fake()->image('bundle-photo.jpg')],
            ],
            [
                'Accept' => 'application/json',
            ]
        )->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $photo = ProductPhoto::query()
            ->where('photoable_type', Bundle::class)
            ->where('photoable_id', $bundle->id)
            ->first();

        expect($photo)->not->toBeNull();

        Storage::disk('public')->assertExists($photo->path);

        $this->actingAs($user)
            ->getJson("/api/v1/bundles/{$bundle->id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.photos')
            ->assertJsonPath('data.photos.0.id', $photo->id);
    });
});
