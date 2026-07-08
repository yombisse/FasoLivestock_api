<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\Espece;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncPhase1OptimisticLockingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that concurrent updates with same starting version reject the second with conflict
     */
    public function test_concurrent_update_rejects_second_with_conflict(): void
    {
        // Setup: Create user, farm, and an animal
        $user = User::factory()->create();
        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();
        
        $animal = Animal::factory()->create([
            'farm_id' => $farm->id,
            'espece_id' => $espece->id,
            'version' => 1,
        ]);

        // First update request (version 1 -> should succeed to version 2)
        $firstUpdateData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'update',
                    'data' => [
                        'id' => $animal->id,
                        'numero_identification' => $animal->numero_identification,
                        'nom' => 'Updated Name 1',
                        'version' => 1, // Starting version
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
        ];

        $firstResponse = $this->actingAs($user)
            ->postJson('/api/sync/push', $firstUpdateData);

        $firstResponse->assertStatus(200);
        $firstResponse->assertJsonPath('data.results.0.status', 'updated');
        
        // Refresh animal from database
        $animal->refresh();
        $this->assertEquals(2, $animal->version);

        // Second update request with OLD version (version 1, but server now has version 2)
        // This should be REJECTED with conflict
        $secondUpdateData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'update',
                    'data' => [
                        'id' => $animal->id,
                        'numero_identification' => $animal->numero_identification,
                        'nom' => 'Updated Name 2',
                        'version' => 1, // OLD version - should conflict
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
        ];

        $secondResponse = $this->actingAs($user)
            ->postJson('/api/sync/push', $secondUpdateData);

        $secondResponse->assertStatus(200);
        $secondResponse->assertJsonPath('data.results.0.status', 'conflict');
        $secondResponse->assertJsonPath('data.results.0.reason', 'Version mismatch (client: 1, server: 2)');
        $secondResponse->assertJsonPath('data.results.0.client_version', 1);
        $secondResponse->assertJsonPath('data.results.0.server_version', 2);
        
        // Verify animal was NOT updated by the second request
        $animal->refresh();
        $this->assertEquals(2, $animal->version);
        $this->assertEquals('Updated Name 1', $animal->nom);
    }

    /**
     * Test that concurrent deletes with same starting version reject the second with conflict
     */
    public function test_concurrent_delete_rejects_second_with_conflict(): void
    {
        // Setup
        $user = User::factory()->create();
        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();
        
        $animal = Animal::factory()->create([
            'farm_id' => $farm->id,
            'espece_id' => $espece->id,
            'version' => 1,
        ]);

        // First delete request (version 1 -> should succeed)
        $firstDeleteData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'delete',
                    'data' => [
                        'id' => $animal->id,
                        'version' => 1,
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
        ];

        $firstResponse = $this->actingAs($user)
            ->postJson('/api/sync/push', $firstDeleteData);

        $firstResponse->assertStatus(200);
        $firstResponse->assertJsonPath('data.results.0.status', 'deleted');
        
        // Restore the animal for the second test
        $animal->restore();
        $animal->version = 2; // Version is now 2 after first delete
        $animal->save();

        // Second delete request with OLD version (version 1, but server now has version 2)
        $secondDeleteData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'delete',
                    'data' => [
                        'id' => $animal->id,
                        'version' => 1, // OLD version - should conflict
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
        ];

        $secondResponse = $this->actingAs($user)
            ->postJson('/api/sync/push', $secondDeleteData);

        $secondResponse->assertStatus(200);
        $secondResponse->assertJsonPath('data.results.0.status', 'conflict');
        $secondResponse->assertJsonPath('data.results.0.reason', 'Version mismatch (client: 1, server: 2)');
        $secondResponse->assertJsonPath('data.results.0.client_version', 1);
        $secondResponse->assertJsonPath('data.results.0.server_version', 2);
    }

    /**
     * Test that update with correct version succeeds
     */
    public function test_update_with_correct_version_succeeds(): void
    {
        // Setup
        $user = User::factory()->create();
        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();
        
        $animal = Animal::factory()->create([
            'farm_id' => $farm->id,
            'espece_id' => $espece->id,
            'version' => 5,
        ]);

        // Update with correct version (5 -> should succeed to 6)
        $updateData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'update',
                    'data' => [
                        'id' => $animal->id,
                        'numero_identification' => $animal->numero_identification,
                        'nom' => 'Updated Name',
                        'version' => 5, // Correct current version
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/sync/push', $updateData);

        $response->assertStatus(200);
        $response->assertJsonPath('data.results.0.status', 'updated');
        
        $animal->refresh();
        $this->assertEquals(6, $animal->version);
        $this->assertEquals('Updated Name', $animal->nom);
    }
}
