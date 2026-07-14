<?php

use App\Models\User;
use App\Models\Farm;
use App\Models\Animal;
use App\Models\Evenement;
use App\Models\Transaction;
use App\Models\Espece;
use App\Models\Categorie;
use App\Models\TypeEvenement;
use App\Models\Lot;
use App\Models\Notification;
use App\Models\Naissance;
use Illuminate\Support\Str;
use Carbon\Carbon;

beforeEach(function () {
    // Clean up database before each test
    DB::table('animals')->delete();
    DB::table('evenements')->delete();
    DB::table('transactions')->delete();
    DB::table('especes')->delete();
    DB::table('categories')->delete();
    DB::table('type_evenements')->delete();
    DB::table('lots')->delete();
    DB::table('notifications')->delete();
    DB::table('naissances')->delete();
    DB::table('farms')->delete();
    DB::table('farm_user')->delete();
});

test('pull returns WatermelonDB format with created/updated/deleted arrays', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create(['owner_id' => $user->id]);
    
    // Create test data with different timestamps
    $lastSyncAt = Carbon::now()->subDays(2);
    $snapshotTime = Carbon::now()->subDay();
    
    // Create an animal before last_sync_at (should not appear)
    $oldAnimal = Animal::factory()->create([
        'farm_id' => $farm->id,
        'created_at' => Carbon::now()->subDays(3),
        'updated_at' => Carbon::now()->subDays(3),
    ]);
    
    // Create an animal after last_sync_at (should appear in created)
    $newAnimal = Animal::factory()->create([
        'farm_id' => $farm->id,
        'created_at' => Carbon::now()->subHours(12),
        'updated_at' => Carbon::now()->subHours(12),
    ]);
    
    // Create and update an animal (should appear in updated)
    $updatedAnimal = Animal::factory()->create([
        'farm_id' => $farm->id,
        'created_at' => Carbon::now()->subDays(3),
        'updated_at' => Carbon::now()->subHours(6),
    ]);
    
    // Create and soft-delete an animal (should appear in deleted)
    $deletedAnimal = Animal::factory()->create([
        'farm_id' => $farm->id,
        'created_at' => Carbon::now()->subDays(3),
        'deleted_at' => Carbon::now()->subHours(3),
    ]);
    
    $response = $this->actingAs($user)
        ->postJson('/api/sync/pull', [
            'last_pulled_at' => $lastSyncAt->toIso8601String(),
            'farm_id' => $farm->id,
        ]);
    
    $response->assertStatus(200);
    
    $data = $response->json('data');
    
    // Verify response structure
    expect($data)->toHaveKey('changes');
    expect($data)->toHaveKey('timestamp');
    
    // Verify animals format
    expect($data['changes'])->toHaveKey('animals');
    expect($data['changes']['animals'])->toHaveKeys(['created', 'updated', 'deleted']);
    
    // Verify categorization
    expect($data['changes']['animals']['created'])->toHaveCount(1);
    expect($data['changes']['animals']['updated'])->toHaveCount(1);
    expect($data['changes']['animals']['deleted'])->toHaveCount(1);
    
    // Verify old animal is not included
    $createdIds = collect($data['changes']['animals']['created'])->pluck('id');
    $updatedIds = collect($data['changes']['animals']['updated'])->pluck('id');
    $deletedIds = collect($data['changes']['animals']['deleted'])->pluck('id');
    
    expect($createdIds)->toContain($newAnimal->id);
    expect($updatedIds)->toContain($updatedAnimal->id);
    expect($deletedIds)->toContain($deletedAnimal->id);
    expect($createdIds)->not->toContain($oldAnimal->id);
    expect($updatedIds)->not->toContain($oldAnimal->id);
    expect($deletedIds)->not->toContain($oldAnimal->id);
    
    // Verify metadata is removed
    $createdAnimal = $data['changes']['animals']['created'][0];
    expect($createdAnimal)->not->toHaveKey('sync_status');
    expect($createdAnimal)->not->toHaveKey('last_modified_by');
    expect($createdAnimal)->not->toHaveKey('version');
    
    // Verify essential timestamps are present
    expect($createdAnimal)->toHaveKey('created_at');
    expect($createdAnimal)->toHaveKey('updated_at');
    expect($createdAnimal)->toHaveKey('deleted_at');
});

test('pull filters by farm_id and verifies user access', function () {
    $user = User::factory()->create();
    $farm1 = Farm::factory()->create(['owner_id' => $user->id]);
    $farm2 = Farm::factory()->create(['owner_id' => User::factory()->create()->id]);
    
    // Create animals in both farms
    $animalInFarm1 = Animal::factory()->create([
        'farm_id' => $farm1->id,
        'created_at' => Carbon::now()->subHours(6),
        'updated_at' => Carbon::now()->subHours(6),
    ]);
    
    $animalInFarm2 = Animal::factory()->create([
        'farm_id' => $farm2->id,
        'created_at' => Carbon::now()->subHours(6),
        'updated_at' => Carbon::now()->subHours(6),
    ]);
    
    $lastSyncAt = Carbon::now()->subDays(1);
    
    // Request for farm1 (user has access)
    $response = $this->actingAs($user)
        ->postJson('/api/sync/pull', [
            'last_pulled_at' => $lastSyncAt->toIso8601String(),
            'farm_id' => $farm1->id,
        ]);
    
    $response->assertStatus(200);
    
    $data = $response->json('data');
    
    // Should only return animals from farm1
    $allAnimalIds = collect($data['changes']['animals']['created'])
        ->concat($data['changes']['animals']['updated'])
        ->concat($data['changes']['animals']['deleted'])
        ->pluck('id');
    
    expect($allAnimalIds)->toContain($animalInFarm1->id);
    expect($allAnimalIds)->not->toContain($animalInFarm2->id);
    
    // Request for farm2 (user does not have access)
    $responseUnauthorized = $this->actingAs($user)
        ->postJson('/api/sync/pull', [
            'last_pulled_at' => $lastSyncAt->toIso8601String(),
            'farm_id' => $farm2->id,
        ]);
    
    $responseUnauthorized->assertStatus(403);
});

test('pull includes all required tables in WatermelonDB format', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create(['owner_id' => $user->id]);
    
    $lastSyncAt = Carbon::now()->subDays(1);
    
    $response = $this->actingAs($user)
        ->postJson('/api/sync/pull', [
            'last_pulled_at' => $lastSyncAt->toIso8601String(),
            'farm_id' => $farm->id,
        ]);
    
    $response->assertStatus(200);
    
    $data = $response->json('data');
    
    // Verify all required tables are present
    $requiredTables = [
        'animals',
        'transactions',
        'evenements',
        'lots',
        'notifications',
        'naissances',
        'especes',
        'categories',
        'type_evenements',
        'farms',
        'farm_user',
    ];
    
    foreach ($requiredTables as $table) {
        expect($data['changes'])->toHaveKey($table);
        expect($data['changes'][$table])->toHaveKeys(['created', 'updated', 'deleted']);
    }
});

test('pull correctly categorizes reference table changes', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create(['owner_id' => $user->id]);
    
    $lastSyncAt = Carbon::now()->subDays(1);
    
    // Create reference data
    $newEspece = Espece::factory()->create([
        'created_at' => Carbon::now()->subHours(6),
        'updated_at' => Carbon::now()->subHours(6),
    ]);
    
    $response = $this->actingAs($user)
        ->postJson('/api/sync/pull', [
            'last_pulled_at' => $lastSyncAt->toIso8601String(),
            'farm_id' => $farm->id,
        ]);
    
    $response->assertStatus(200);
    
    $data = $response->json('data');
    
    // Reference tables should be included without farm_id filter
    expect($data['changes']['especes']['created'])->toHaveCount(1);
    expect($data['changes']['especes']['created'][0]['id'])->toBe($newEspece->id);
});

test('pull handles empty changes correctly', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create(['owner_id' => $user->id]);
    
    $lastSyncAt = Carbon::now()->subMinutes(5);
    
    $response = $this->actingAs($user)
        ->postJson('/api/sync/pull', [
            'last_pulled_at' => $lastSyncAt->toIso8601String(),
            'farm_id' => $farm->id,
        ]);
    
    $response->assertStatus(200);
    
    $data = $response->json('data');
    
    // All tables should be present with empty arrays
    $requiredTables = [
        'animals',
        'transactions',
        'evenements',
        'lots',
        'notifications',
        'naissances',
        'especes',
        'categories',
        'type_evenements',
        'farms',
        'farm_user',
    ];
    
    foreach ($requiredTables as $table) {
        expect($data['changes'][$table]['created'])->toBeArray();
        expect($data['changes'][$table]['updated'])->toBeArray();
        expect($data['changes'][$table]['deleted'])->toBeArray();
    }
});
