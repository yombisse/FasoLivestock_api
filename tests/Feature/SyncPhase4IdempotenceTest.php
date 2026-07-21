<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\Espece;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncPhase4IdempotenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that push with sync_request_id is processed normally
     */
    public function test_push_with_sync_request_id_processes_normally(): void
    {
        $fullRole = \Spatie\Permission\Models\Role::create(['name' => 'full_access', 'guard_name' => 'api']);
        $createPermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'animals.create', 'guard_name' => 'api']);
        $fullRole->givePermissionTo($createPermission);

        $user = User::factory()->create();
        $user->assignRole($fullRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();

        $syncRequestId = substr(Str::random(20), 0, 20);

        // Push with sync_request_id
        $createData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'create',
                    'data' => [
                        'id' => substr(Str::random(20), 0, 20),
                        'numero_identification' => 'TAG123',
                        'nom' => 'Test Animal',
                        'sexe' => 'male',
                        'espece_id' => $espece->id,
                        'farm_id' => $farm->id,
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
            'sync_request_id' => $syncRequestId,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/sync/push', $createData);

        $response->assertStatus(200);
        $response->assertJsonPath('data.results.0.status', 'created');
        $response->assertJsonPath('data.cached', null); // Not cached on first request

        // Verify sync_request was stored
        $this->assertDatabaseHas('sync_requests', [
            'id' => $syncRequestId,
            'status' => 'completed',
        ]);
    }

    /**
     * Test that duplicate push with same sync_request_id returns cached results
     */
    public function test_duplicate_push_returns_cached_results(): void
    {
        $fullRole = \Spatie\Permission\Models\Role::create(['name' => 'full_access', 'guard_name' => 'api']);
        $createPermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'animals.create', 'guard_name' => 'api']);
        $fullRole->givePermissionTo($createPermission);

        $user = User::factory()->create();
        $user->assignRole($fullRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();

        $syncRequestId = substr(Str::random(20), 0, 20);
        $animalId = substr(Str::random(20), 0, 20);

        // First push
        $createData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'create',
                    'data' => [
                        'id' => $animalId,
                        'numero_identification' => 'TAG456',
                        'nom' => 'Test Animal',
                        'sexe' => 'male',
                        'espece_id' => $espece->id,
                        'farm_id' => $farm->id,
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
            'sync_request_id' => $syncRequestId,
        ];

        $firstResponse = $this->actingAs($user)
            ->postJson('/api/sync/push', $createData);

        $firstResponse->assertStatus(200);
        $firstResponse->assertJsonPath('data.results.0.status', 'created');
        $firstResponse->assertJsonPath('data.cached', null);

        // Second push with SAME sync_request_id
        $secondResponse = $this->actingAs($user)
            ->postJson('/api/sync/push', $createData);

        $secondResponse->assertStatus(200);
        $secondResponse->assertJsonPath('data.cached', true); // Should be cached
        $secondResponse->assertJsonPath('data.results.0.status', 'created');

        // Verify animal was only created once (no duplicate)
        $this->assertDatabaseCount('animals', 1);
    }

    /**
     * Test that push without sync_request_id works normally (backward compatibility)
     */
    public function test_push_without_sync_request_id_works_normally(): void
    {
        $fullRole = \Spatie\Permission\Models\Role::create(['name' => 'full_access2', 'guard_name' => 'api']);
        $createPermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'animals.create', 'guard_name' => 'api']);
        $fullRole->givePermissionTo($createPermission);

        $user = User::factory()->create();
        $user->assignRole($fullRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();

        // Push WITHOUT sync_request_id (old mobile version)
        $createData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'create',
                    'data' => [
                        'id' => substr(Str::random(20), 0, 20),
                        'numero_identification' => 'TAG789',
                        'nom' => 'Test Animal',
                        'sexe' => 'male',
                        'espece_id' => $espece->id,
                        'farm_id' => $farm->id,
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
            // No sync_request_id
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/sync/push', $createData);

        $response->assertStatus(200);
        $response->assertJsonPath('data.results.0.status', 'created');
        $response->assertJsonPath('data.cached', null);

        // Verify animal was created
        $this->assertDatabaseHas('animals', [
            'numero_identification' => 'TAG789',
        ]);
    }

    /**
     * Test that different sync_request_ids are processed independently
     */
    public function test_different_sync_request_ids_processed_independently(): void
    {
        $fullRole = \Spatie\Permission\Models\Role::create(['name' => 'full_access3', 'guard_name' => 'api']);
        $createPermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'animals.create', 'guard_name' => 'api']);
        $fullRole->givePermissionTo($createPermission);

        $user = User::factory()->create();
        $user->assignRole($fullRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();

        $syncRequestId1 = substr(Str::random(20), 0, 20);
        $syncRequestId2 = substr(Str::random(20), 0, 20);

        // First push with sync_request_id1
        $createData1 = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'create',
                    'data' => [
                        'id' => substr(Str::random(20), 0, 20),
                        'numero_identification' => 'TAG111',
                        'nom' => 'Animal 1',
                        'sexe' => 'male',
                        'espece_id' => $espece->id,
                        'farm_id' => $farm->id,
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
            'sync_request_id' => $syncRequestId1,
        ];

        $response1 = $this->actingAs($user)
            ->postJson('/api/sync/push', $createData1);

        $response1->assertStatus(200);
        $response1->assertJsonPath('data.cached', null);

        // Second push with different sync_request_id2
        $createData2 = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'create',
                    'data' => [
                        'id' => substr(Str::random(20), 0, 20),
                        'numero_identification' => 'TAG222',
                        'nom' => 'Animal 2',
                        'sexe' => 'male',
                        'espece_id' => $espece->id,
                        'farm_id' => $farm->id,
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
            'sync_request_id' => $syncRequestId2,
        ];

        $response2 = $this->actingAs($user)
            ->postJson('/api/sync/push', $createData2);

        $response2->assertStatus(200);
        $response2->assertJsonPath('data.cached', null); // Not cached, different ID

        // Verify both animals were created
        $this->assertDatabaseCount('animals', 2);
    }
}
