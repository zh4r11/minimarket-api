<?php

declare(strict_types=1);

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Suppliers', function (): void {
    it('requires authentication', function (): void {
        $this->getJson('/api/v1/suppliers')->assertStatus(401);
    });

    it('returns list of suppliers', function (): void {
        $user = User::factory()->create();
        Supplier::factory()->count(3)->create();
        $this->actingAs($user)->getJson('/api/v1/suppliers')
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'data' => ['data']]);
    });

    it('creates a supplier', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/suppliers', [
            'name' => 'Test Supplier',
            'email' => 'supplier@example.com',
            'phone' => '1234567890',
            'city' => 'Jakarta',
        ])->assertStatus(201)
          ->assertJsonPath('data.name', 'Test Supplier');
        $this->assertDatabaseHas('suppliers', ['name' => 'Test Supplier']);
    });

    it('shows a supplier', function (): void {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $this->actingAs($user)->getJson("/api/v1/suppliers/{$supplier->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $supplier->id);
    });

    it('updates a supplier', function (): void {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $this->actingAs($user)->putJson("/api/v1/suppliers/{$supplier->id}", ['name' => 'Updated Supplier'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Supplier');
    });

    it('deletes a supplier', function (): void {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $this->actingAs($user)->deleteJson("/api/v1/suppliers/{$supplier->id}")
            ->assertStatus(204);
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    });

    it('validates required fields on store', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/suppliers', [])
            ->assertStatus(422);
    });
});
