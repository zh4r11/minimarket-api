<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Categories', function (): void {
    it('requires authentication', function (): void {
        $this->getJson('/api/v1/categories')->assertStatus(401);
    });

    it('returns list of categories', function (): void {
        $user = User::factory()->create();
        Category::factory()->count(3)->create();
        $this->actingAs($user)->getJson('/api/v1/categories')
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'data' => ['data']]);
    });

    it('creates a category', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/categories', [
            'name' => 'Test Category',
            'description' => 'Test Description',
        ])->assertStatus(201)
          ->assertJsonPath('data.name', 'Test Category')
          ->assertJsonPath('data.slug', 'test-category');
        $this->assertDatabaseHas('categories', ['name' => 'Test Category']);
    });

    it('shows a category', function (): void {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $this->actingAs($user)->getJson("/api/v1/categories/{$category->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $category->id);
    });

    it('updates a category', function (): void {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $this->actingAs($user)->putJson("/api/v1/categories/{$category->id}", ['name' => 'Updated'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated');
    });

    it('deletes a category', function (): void {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $this->actingAs($user)->deleteJson("/api/v1/categories/{$category->id}")
            ->assertStatus(204);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    });

    it('validates required fields on store', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/categories', [])
            ->assertStatus(422);
    });
});
