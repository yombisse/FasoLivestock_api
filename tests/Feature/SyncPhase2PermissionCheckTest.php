<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\Espece;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncPhase2PermissionCheckTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that user without animals.create permission is rejected
     */
    public function test_user_without_create_permission_is_rejected(): void
    {
        // Setup: Create restricted role with only view permission
        $restrictedRole = Role::create(['name' => 'restricted', 'guard_name' => 'api']);
        $viewPermission = Permission::firstOrCreate(['name' => 'animals.view', 'guard_name' => 'api']);
        $restrictedRole->givePermissionTo($viewPermission);

        // Create user with restricted role
        $user = User::factory()->create();
        $user->assignRole($restrictedRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();

        // Try to create an animal without permission
        $createData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'create',
                    'data' => [
                        'id' => substr(\Illuminate\Support\Str::random(20), 0, 20),
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
        $response->assertJsonPath('data.results.0.reason', 'Permission refusée');

        // Verify animal was NOT created
        $this->assertDatabaseMissing('animals', [
            'numero_identification' => 'TAG123',
        ]);
    }

    /**
     * Test that user with animals.create permission succeeds
     */
    public function test_user_with_create_permission_succeeds(): void
    {
        // Setup: Create role with create permission
        $fullRole = Role::create(['name' => 'full_access', 'guard_name' => 'api']);
        $createPermission = Permission::firstOrCreate(['name' => 'animals.create', 'guard_name' => 'api']);
        $fullRole->givePermissionTo($createPermission);

        // Create user with full role
        $user = User::factory()->create();
        $user->assignRole($fullRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();

        // Try to create an animal with permission
        $createData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'create',
                    'data' => [
                        'id' => substr(\Illuminate\Support\Str::random(20), 0, 20),
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

        if ($response->status() !== 200) {
            dump($response->json());
        }
        $response->assertStatus(200);
        $response->assertJsonPath('data.results.0.status', 'created');

        // Verify animal WAS created
        $this->assertDatabaseHas('animals', [
            'numero_identification' => 'TAG456',
        ]);
    }

    /**
     * Test that mixed batch continues processing after permission denial
     */
    public function test_mixed_batch_continues_after_permission_denial(): void
    {
        // Setup: Create role with only update permission, no create permission
        $restrictedRole = Role::create(['name' => 'update_only', 'guard_name' => 'api']);
        $updatePermission = Permission::firstOrCreate(['name' => 'animals.update', 'guard_name' => 'api']);
        $restrictedRole->givePermissionTo($updatePermission);

        $user = User::factory()->create();
        $user->assignRole($restrictedRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();

        // Create an existing animal
        $animal = Animal::factory()->create([
            'farm_id' => $farm->id,
            'espece_id' => $espece->id,
            'version' => 1,
        ]);

        // Try to create (should fail) and update (should succeed) in same batch
        $mixedData = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'create',
                    'data' => [
                        'id' => substr(\Illuminate\Support\Str::random(20), 0, 20),
                        'numero_identification' => 'TAG_NEW',
                        'nom' => 'New Animal',
                        'sexe' => 'male',
                        'espece_id' => $espece->id,
                        'farm_id' => $farm->id,
                    ],
                ],
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
            ->postJson('/api/sync/push', $mixedData);

        $response->assertStatus(200);
        
        // First item (create) should fail with permission error
        $response->assertJsonPath('data.results.0.status', 'error');
        $response->assertJsonPath('data.results.0.reason', 'Permission refusée');
        
        // Second item (update) should succeed
        $response->assertJsonPath('data.results.1.status', 'updated');

        // Verify create did NOT happen
        $this->assertDatabaseMissing('animals', [
            'numero_identification' => 'TAG_NEW',
        ]);

        // Verify update DID happen
        $animal->refresh();
        $this->assertEquals('Updated Name', $animal->nom);
    }

    /**
     * Test that delete permission is checked
     */
    public function test_delete_permission_is_checked(): void
    {
        // Setup: Create role with only view permission
        $viewOnlyRole = Role::create(['name' => 'view_only', 'guard_name' => 'api']);
        $viewPermission = Permission::firstOrCreate(['name' => 'animals.view', 'guard_name' => 'api']);
        $viewOnlyRole->givePermissionTo($viewPermission);

        $user = User::factory()->create();
        $user->assignRole($viewOnlyRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();

        $animal = Animal::factory()->create([
            'farm_id' => $farm->id,
            'espece_id' => $espece->id,
            'version' => 1,
        ]);

        // Try to delete without permission
        $deleteData = [
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

        $response = $this->actingAs($user)
            ->postJson('/api/sync/push', $deleteData);

        $response->assertStatus(200);
        $response->assertJsonPath('data.results.0.status', 'error');
        $response->assertJsonPath('data.results.0.reason', 'Permission refusée');

        // Verify animal was NOT deleted
        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'deleted_at' => null,
        ]);
    }
}
