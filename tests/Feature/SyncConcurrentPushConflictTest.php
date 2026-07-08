<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\Espece;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncConcurrentPushConflictTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test d'intégration: deux pushes concurrents sur le même animal
     * Le premier doit réussir, le second doit retourner VERSION_CONFLICT
     */
    public function test_concurrent_pushes_same_animal_first_succeeds_second_conflicts(): void
    {
        $fullRole = \Spatie\Permission\Models\Role::create(['name' => 'full_access', 'guard_name' => 'api']);
        $updatePermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'animals.update', 'guard_name' => 'api']);
        $fullRole->givePermissionTo($updatePermission);

        $user = User::factory()->create();
        $user->assignRole($fullRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();

        // Créer un animal avec version initiale = 1
        $animal = Animal::factory()->create([
            'farm_id' => $farm->id,
            'espece_id' => $espece->id,
            'version' => 1,
        ]);

        // Premier push: update avec version 1 (doit réussir)
        $firstPush = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'update',
                    'data' => [
                        'id' => $animal->id,
                        'numero_identification' => $animal->numero_identification,
                        'nom' => 'First Update',
                        'version' => 1, // Version actuelle en base
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
        ];

        $firstResponse = $this->actingAs($user)
            ->postJson('/api/sync/push', $firstPush);

        $firstResponse->assertStatus(200);
        $firstResponse->assertJsonPath('data.results.0.status', 'updated');

        // Recharger l'animal pour vérifier que la version a été incrémentée
        $animal->refresh();
        $this->assertEquals(2, $animal->version);

        // Deuxième push: update avec version 1 (ancienne version, doit confliter)
        $secondPush = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'update',
                    'data' => [
                        'id' => $animal->id,
                        'numero_identification' => $animal->numero_identification,
                        'nom' => 'Second Update',
                        'version' => 1, // Version obsolète (la base est maintenant à 2)
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
        ];

        $secondResponse = $this->actingAs($user)
            ->postJson('/api/sync/push', $secondPush);

        $secondResponse->assertStatus(200);
        
        // Vérifier le statut de conflit
        $secondResponse->assertJsonPath('data.results.0.status', 'conflict');
        
        // Vérifier le code d'erreur VERSION_CONFLICT
        $secondResponse->assertJsonPath('data.results.0.code', 'VERSION_CONFLICT');
        
        // Vérifier que client_version et server_version sont présents
        $secondResponse->assertJsonPath('data.results.0.client_version', 1);
        $secondResponse->assertJsonPath('data.results.0.server_version', 2);

        // Vérifier que l'animal n'a pas été modifié par le deuxième push
        $animal->refresh();
        $this->assertEquals('First Update', $animal->nom);
        $this->assertEquals(2, $animal->version);
    }

    /**
     * Test: deux pushes concurrents avec même version de départ
     * Simule un scénario de race condition
     */
    public function test_concurrent_pushes_same_starting_version(): void
    {
        $fullRole = \Spatie\Permission\Models\Role::create(['name' => 'full_access2', 'guard_name' => 'api']);
        $updatePermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'animals.update', 'guard_name' => 'api']);
        $fullRole->givePermissionTo($updatePermission);

        $user = User::factory()->create();
        $user->assignRole($fullRole);

        $farm = Farm::factory()->create(['owner_id' => $user->id]);
        $espece = Espece::factory()->create();

        // Créer un animal avec version initiale = 5
        $animal = Animal::factory()->create([
            'farm_id' => $farm->id,
            'espece_id' => $espece->id,
            'version' => 5,
        ]);

        // Premier push concurrent
        $push1 = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'update',
                    'data' => [
                        'id' => $animal->id,
                        'numero_identification' => $animal->numero_identification,
                        'nom' => 'Concurrent Update 1',
                        'version' => 5,
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
        ];

        $response1 = $this->actingAs($user)
            ->postJson('/api/sync/push', $push1);

        $response1->assertStatus(200);
        $response1->assertJsonPath('data.results.0.status', 'updated');

        // Deuxième push concurrent avec la même version de départ
        $push2 = [
            'changes' => [
                [
                    'table' => 'animals',
                    'action' => 'update',
                    'data' => [
                        'id' => $animal->id,
                        'numero_identification' => $animal->numero_identification,
                        'nom' => 'Concurrent Update 2',
                        'version' => 5, // Même version de départ
                    ],
                ],
            ],
            'last_sync_at' => now()->subHour()->toIso8601String(),
            'farm_id' => $farm->id,
        ];

        $response2 = $this->actingAs($user)
            ->postJson('/api/sync/push', $push2);

        $response2->assertStatus(200);
        $response2->assertJsonPath('data.results.0.status', 'conflict');
        $response2->assertJsonPath('data.results.0.code', 'VERSION_CONFLICT');
        $response2->assertJsonPath('data.results.0.client_version', 5);
        $response2->assertJsonPath('data.results.0.server_version', 6);
    }
}
