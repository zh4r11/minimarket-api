<?php

declare(strict_types=1);

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Units', function (): void {
    it('requires authentication', function (): void {
        $this->getJson('/api/v1/units')->assertStatus(401);
    });

    it('returns list of units', function (): void {
        $user = User::factory()->create();
        Unit::factory()->count(3)->create();
        $this->actingAs($user)->getJson('/api/v1/units')
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'data' => ['data']]);
    });

    it('creates a unit', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/units', [
            'name' => 'Kilogram',
            'symbol' => 'kg',
        ])->assertStatus(201)
          ->assertJsonPath('data.name', 'Kilogram')
          ->assertJsonPath('data.symbol', 'kg');
        $this->assertDatabaseHas('units', ['name' => 'Kilogram']);
    });

    it('shows a unit', function (): void {
        $user = User::factory()->create();
        $unit = Unit::factory()->create();
        $this->actingAs($user)->getJson("/api/v1/units/{$unit->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $unit->id);
    });

    it('updates a unit', function (): void {
        $user = User::factory()->create();
        $unit = Unit::factory()->create();
        $this->actingAs($user)->putJson("/api/v1/units/{$unit->id}", ['name' => 'Updated Unit', 'symbol' => 'uu'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Unit');
    });

    it('deletes a unit', function (): void {
        $user = User::factory()->create();
        $unit = Unit::factory()->create();
        $this->actingAs($user)->deleteJson("/api/v1/units/{$unit->id}")
            ->assertStatus(204);
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    });

    it('validates required fields on store', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/units', [])
            ->assertStatus(422);
    });
});
