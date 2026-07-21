<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\Espece;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncPhase3ErrorCodesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that version conflict returns correct error code
     */
    public function test_version_conflict_returns_correct_code(): void
    {
        $fullRole = \Spatie\Permission\Models\Role::create(['name' => 'full_access', 'guard_name' => 'api']);
        $updatePermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'animals.update', 'guard_name' => 'api']);
        $fullRole->givePermissionTo($updatePermission);

        $user = User::factory()->create();
        $user->assignRole($fullRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();
        
        $animal = Animal::factory()->create([
            'farm_id' => $farm->id,
            'espece_id' => $espece->id,
            'version' => 2,
        ]);

        // Try to update with old version (1 instead of 2)
        $updateData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'update',
                    'data' => [
                        'id' => $animal->id,
                        'numero_identification' => $animal->numero_identification,
                        'nom' => 'Updated Name',
                        'version' => 1, // Wrong version
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/sync/push', $updateData);

        $response->assertStatus(200);
        $response->assertJsonPath('data.results.0.status', 'conflict');
        $response->assertJsonPath('data.results.0.code', 'VERSION_CONFLICT');
        $response->assertJsonPath('data.results.0.client_version', 1);
        $response->assertJsonPath('data.results.0.server_version', 2);
    }

    /**
     * Test that invalid UUID returns correct error code
     */
    public function test_invalid_uuid_returns_correct_code(): void
    {
        $fullRole = \Spatie\Permission\Models\Role::create(['name' => 'full_access2', 'guard_name' => 'api']);
        $createPermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'animals.create', 'guard_name' => 'api']);
        $fullRole->givePermissionTo($createPermission);

        $user = User::factory()->create();
        $user->assignRole($fullRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();

        // Try to create with invalid UUID
        $createData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'create',
                    'data' => [
                        'id' => 'not-a-uuid',
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
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/sync/push', $createData);

        $response->assertStatus(200);
        $response->assertJsonPath('data.results.0.status', 'error');
        $response->assertJsonPath('data.results.0.code', 'UUID_INVALID');
    }

    /**
     * Test that ID already exists returns correct error code
     */
    public function test_id_exists_returns_correct_code(): void
    {
        $fullRole = \Spatie\Permission\Models\Role::create(['name' => 'full_access3', 'guard_name' => 'api']);
        $createPermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'animals.create', 'guard_name' => 'api']);
        $fullRole->givePermissionTo($createPermission);

        $user = User::factory()->create();
        $user->assignRole($fullRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();
        
        $existingAnimal = Animal::factory()->create([
            'farm_id' => $farm->id,
            'espece_id' => $espece->id,
        ]);

        // Try to create with same ID
        $createData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'create',
                    'data' => [
                        'id' => $existingAnimal->id,
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
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/sync/push', $createData);

        $response->assertStatus(200);
        $response->assertJsonPath('data.results.0.status', 'error');
        $response->assertJsonPath('data.results.0.code', 'ID_EXISTS');
    }

    /**
     * Test that permission denied returns correct error code
     */
    public function test_permission_denied_returns_correct_code(): void
    {
        $restrictedRole = \Spatie\Permission\Models\Role::create(['name' => 'restricted', 'guard_name' => 'api']);
        $viewPermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'animals.view', 'guard_name' => 'api']);
        $restrictedRole->givePermissionTo($viewPermission);

        $user = User::factory()->create();
        $user->assignRole($restrictedRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();

        // Try to create without permission
        $createData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'create',
                    'data' => [
                        'id' => substr(\Illuminate\Support\Str::random(20), 0, 20),
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
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/sync/push', $createData);

        $response->assertStatus(200);
        $response->assertJsonPath('data.results.0.status', 'error');
        $response->assertJsonPath('data.results.0.code', 'PERMISSION_DENIED');
    }

    /**
     * Test that successful operations don't include error code
     */
    public function test_successful_operations_dont_include_error_code(): void
    {
        $fullRole = \Spatie\Permission\Models\Role::create(['name' => 'full_access', 'guard_name' => 'api']);
        $createPermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'animals.create', 'guard_name' => 'api']);
        $fullRole->givePermissionTo($createPermission);

        $user = User::factory()->create();
        $user->assignRole($fullRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();

        // Successful create
        $createData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'create',
                    'data' => [
                        'id' => substr(\Illuminate\Support\Str::random(20), 0, 20),
                        'numero_identification' => 'TAG999',
                        'nom' => 'Test Animal',
                        'sexe' => 'male',
                        'espece_id' => $espece->id,
                        'farm_id' => $farm->id,
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/sync/push', $createData);

        $response->assertStatus(200);
        $response->assertJsonPath('data.results.0.status', 'created');
        // Code should be null for successful operations
        $response->assertJsonPath('data.results.0.code', null);
    }

    /**
     * Test backward compatibility - old mobile clients ignoring 'code' still work
     */
    public function test_backward_compatibility_status_field_still_works(): void
    {
        $fullRole = \Spatie\Permission\Models\Role::create(['name' => 'full_access4', 'guard_name' => 'api']);
        $updatePermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'animals.update', 'guard_name' => 'api']);
        $fullRole->givePermissionTo($updatePermission);

        $user = User::factory()->create();
        $user->assignRole($fullRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();
        
        $animal = Animal::factory()->create([
            'farm_id' => $farm->id,
            'espece_id' => $espece->id,
            'version' => 2,
        ]);

        // Trigger version conflict
        $updateData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'update',
                    'data' => [
                        'id' => $animal->id,
                        'numero_identification' => $animal->numero_identification,
                        'nom' => 'Updated Name',
                        'version' => 1,
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/sync/push', $updateData);

        // Old mobile clients check 'status' field - this should still work
        $response->assertJsonPath('data.results.0.status', 'conflict');
        
        // New 'code' field is also present for future use
        $response->assertJsonPath('data.results.0.code', 'VERSION_CONFLICT');
    }
}
