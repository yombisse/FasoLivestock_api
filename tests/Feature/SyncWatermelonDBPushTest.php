<?php

use App\Models\User;
use App\Models\Farm;
use App\Models\Animal;
use App\Models\Evenement;
use App\Models\Transaction;
use App\Models\Espece;
use App\Models\Categorie;
use App\Models\TypeEvenement;
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
    DB::table('farms')->delete();
    DB::table('sync_requests')->delete();
});

test('push handles animal + evenement + transaction in wrong order with dependency resolution', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create(['owner_id' => $user->id]);
    
    // Create reference data
    $espece = Espece::factory()->create();
    $typeEvenement = TypeEvenement::factory()->create(['nom_type' => 'Achat']);
    $categorie = Categorie::factory()->create();
    
    // Generate string IDs
    $animalId = substr(Str::random(20), 0, 20);
    $evenementId = substr(Str::random(20), 0, 20);
    $transactionId = substr(Str::random(20), 0, 20);
    
    // Create chunk in WRONG order (transaction before evenement before animal)
    $chunk = [
        'transactions' => [
            'created' => [
                [
                    'id' => $transactionId,
                    'type_transaction' => 'SORTIE',
                    'montant' => 15000,
                    'date_transaction' => Carbon::now()->toIso8601String(),
                    'description' => 'Achat animal',
                    'farm_id' => $farm->id,
                    'animal_id' => $animalId,
                    'evenement_id' => $evenementId,
                    'categorie_id' => $categorie->id,
                    'created_at' => Carbon::now()->toIso8601String(),
                    'updated_at' => Carbon::now()->toIso8601String(),
                ],
            ],
            'updated' => [],
            'deleted' => [],
        ],
        'evenements' => [
            'created' => [
                [
                    'id' => $evenementId,
                    'date_evenement' => Carbon::now()->toIso8601String(),
                    'statut' => 'TERMINE',
                    'farm_id' => $farm->id,
                    'animal_id' => $animalId,
                    'type_evenement_id' => $typeEvenement->id,
                    'created_at' => Carbon::now()->toIso8601String(),
                    'updated_at' => Carbon::now()->toIso8601String(),
                ],
            ],
            'updated' => [],
            'deleted' => [],
        ],
        'animals' => [
            'created' => [
                [
                    'id' => $animalId,
                    'numero_identification' => 'TEST001',
                    'sexe' => 'MALE',
                    'statut' => 'ACTIF',
                    'date_naissance' => Carbon::now()->subYears(2)->toIso8601String(),
                    'farm_id' => $farm->id,
                    'espece_id' => $espece->id,
                    'created_at' => Carbon::now()->toIso8601String(),
                    'updated_at' => Carbon::now()->toIso8601String(),
                ],
            ],
            'updated' => [],
            'deleted' => [],
        ],
    ];
    
    $response = $this->actingAs($user)
        ->postJson('/api/sync/push', [
            'changes' => $chunk,
            'last_sync_at' => Carbon::now()->subDays(1)->toIso8601String(),
            'farm_id' => $farm->id,
        ]);
    
    $response->assertStatus(200);
    
    $data = $response->json('data');
    
    // Verify all items were processed successfully
    expect($data['results'])->toHaveCount(3);
    
    // Verify all items exist in database
    expect(Animal::where('id', $animalId)->exists())->toBeTrue();
    expect(Evenement::where('id', $evenementId)->exists())->toBeTrue();
    expect(Transaction::where('id', $transactionId)->exists())->toBeTrue();
    
    // Verify only one transaction exists
    expect(Transaction::count())->toBe(1);
    
    // Verify transaction has correct FKs
    $transaction = Transaction::find($transactionId);
    expect($transaction->animal_id)->toBe($animalId);
    expect($transaction->evenement_id)->toBe($evenementId);
});

test('push handles event referencing non-existent animal with isolated error', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create(['owner_id' => $user->id]);
    
    $typeEvenement = TypeEvenement::factory()->create(['nom_type' => 'Achat']);
    
    $nonExistentAnimalId = substr(Str::random(20), 0, 20);
    $evenementId = substr(Str::random(20), 0, 20);
    
    // Create a valid animal first
    $validAnimal = Animal::factory()->create([
        'farm_id' => $farm->id,
        'created_at' => Carbon::now()->subHours(6),
        'updated_at' => Carbon::now()->subHours(6),
    ]);
    
    // Create chunk with one valid evenement and one invalid
    $chunk = [
        'evenements' => [
            'created' => [
                [
                    'id' => $evenementId,
                    'date_evenement' => Carbon::now()->toIso8601String(),
                    'statut' => 'TERMINE',
                    'farm_id' => $farm->id,
                    'animal_id' => $nonExistentAnimalId, // Non-existent animal
                    'type_evenement_id' => $typeEvenement->id,
                    'created_at' => Carbon::now()->toIso8601String(),
                    'updated_at' => Carbon::now()->toIso8601String(),
                ],
            ],
            'updated' => [],
            'deleted' => [],
        ],
        'animals' => [
            'created' => [
                [
                    'id' => $validAnimal->id,
                    'numero_identification' => $validAnimal->numero_identification,
                    'sexe' => 'MALE',
                    'statut' => 'ACTIF',
                    'farm_id' => $farm->id,
                    'created_at' => $validAnimal->created_at->toIso8601String(),
                    'updated_at' => $validAnimal->updated_at->toIso8601String(),
                ],
            ],
            'updated' => [],
            'deleted' => [],
        ],
    ];
    
    $response = $this->actingAs($user)
        ->postJson('/api/sync/push', [
            'changes' => $chunk,
            'last_sync_at' => Carbon::now()->subDays(1)->toIso8601String(),
            'farm_id' => $farm->id,
        ]);
    
    $response->assertStatus(200);
    
    $data = $response->json('data');
    
    // Verify both items were processed
    expect($data['results'])->toHaveCount(2);
    
    // Verify the invalid evenement failed with FK_MISSING
    $evenementResult = collect($data['results'])->first(fn($r) => $r['id'] === $evenementId);
    expect($evenementResult['status'])->toBe('error');
    expect($evenementResult['code'])->toBe('FK_MISSING');
    
    // Verify the valid animal was processed successfully
    $animalResult = collect($data['results'])->first(fn($r) => $r['id'] === $validAnimal->id);
    expect($animalResult['status'])->toBe('created');
    
    // Verify valid animal exists in database
    expect(Animal::where('id', $validAnimal->id)->exists())->toBeTrue();
    
    // Verify invalid evenement does not exist
    expect(Evenement::where('id', $evenementId)->exists())->toBeFalse();
});

test('push same chunk twice (network retry) does not create duplicates', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create(['owner_id' => $user->id]);
    
    $espece = Espece::factory()->create();
    
    $animalId = substr(Str::random(20), 0, 20);
    
    $chunk = [
        'animals' => [
            'created' => [
                [
                    'id' => $animalId,
                    'numero_identification' => 'TEST001',
                    'sexe' => 'MALE',
                    'statut' => 'ACTIF',
                    'farm_id' => $farm->id,
                    'espece_id' => $espece->id,
                    'created_at' => Carbon::now()->toIso8601String(),
                    'updated_at' => Carbon::now()->toIso8601String(),
                ],
            ],
            'updated' => [],
            'deleted' => [],
        ],
    ];
    
    // First push
    $response1 = $this->actingAs($user)
        ->postJson('/api/sync/push', [
            'changes' => $chunk,
            'last_sync_at' => Carbon::now()->subDays(1)->toIso8601String(),
            'farm_id' => $farm->id,
        ]);
    
    $response1->assertStatus(200);
    
    // Second push (simulating network retry)
    $response2 = $this->actingAs($user)
        ->postJson('/api/sync/push', [
            'changes' => $chunk,
            'last_sync_at' => Carbon::now()->subDays(1)->toIso8601String(),
            'farm_id' => $farm->id,
        ]);
    
    $response2->assertStatus(200);
    
    // Verify only one animal exists
    expect(Animal::count())->toBe(1);
    expect(Animal::where('id', $animalId)->exists())->toBeTrue();
    
    // Verify second push returned 'updated' status (idempotent)
    $data2 = $response2->json('data');
    $result = $data2['results'][0];
    expect($result['status'])->toBe('updated'); // updateOrCreate returns 'updated' on retry
});

test('push with client transaction on event that already has server transaction ignores client transaction', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create(['owner_id' => $user->id]);
    
    $espece = Espece::factory()->create();
    $typeEvenement = TypeEvenement::factory()->create(['nom_type' => 'Achat']);
    $categorie = Categorie::factory()->create();
    
    // Create animal and event
    $animal = Animal::factory()->create([
        'farm_id' => $farm->id,
        'espece_id' => $espece->id,
    ]);
    
    $evenement = Evenement::factory()->create([
        'farm_id' => $farm->id,
        'animal_id' => $animal->id,
        'type_evenement_id' => $typeEvenement->id,
    ]);
    
    // Simulate server creating a transaction via EvenementTransactionService
    $serverTransaction = Transaction::factory()->create([
        'farm_id' => $farm->id,
        'animal_id' => $animal->id,
        'evenement_id' => $evenement->id,
        'categorie_id' => $categorie->id,
        'type_transaction' => 'SORTIE',
        'montant' => 15000,
    ]);
    
    $clientTransactionId = substr(Str::random(20), 0, 20);
    
    // Client tries to push its own transaction for the same event
    $chunk = [
        'transactions' => [
            'created' => [
                [
                    'id' => $clientTransactionId,
                    'type_transaction' => 'SORTIE',
                    'montant' => 15000,
                    'date_transaction' => Carbon::now()->toIso8601String(),
                    'description' => 'Achat animal (client)',
                    'farm_id' => $farm->id,
                    'animal_id' => $animal->id,
                    'evenement_id' => $evenement->id,
                    'categorie_id' => $categorie->id,
                    'created_at' => Carbon::now()->toIso8601String(),
                    'updated_at' => Carbon::now()->toIso8601String(),
                ],
            ],
            'updated' => [],
            'deleted' => [],
        ],
    ];
    
    $response = $this->actingAs($user)
        ->postJson('/api/sync/push', [
            'changes' => $chunk,
            'last_sync_at' => Carbon::now()->subDays(1)->toIso8601String(),
            'farm_id' => $farm->id,
        ]);
    
    $response->assertStatus(200);
    
    $data = $response->json('data');
    
    // Verify client transaction was ignored
    $result = $data['results'][0];
    expect($result['status'])->toBe('ignored');
    expect($result['reason'])->toContain('Transaction already exists');
    expect($result['existing_id'])->toBe($serverTransaction->id);
    
    // Verify only one transaction exists (the server one)
    expect(Transaction::count())->toBe(1);
    expect(Transaction::where('id', $serverTransaction->id)->exists())->toBeTrue();
    expect(Transaction::where('id', $clientTransactionId)->exists())->toBeFalse();
});

test('push uses sync_request_id for idempotence', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create(['owner_id' => $user->id]);
    
    $espece = Espece::factory()->create();
    
    $animalId = substr(Str::random(20), 0, 20);
    $syncRequestId = substr(Str::random(20), 0, 20);
    
    $chunk = [
        'animals' => [
            'created' => [
                [
                    'id' => $animalId,
                    'numero_identification' => 'TEST001',
                    'sexe' => 'MALE',
                    'statut' => 'ACTIF',
                    'farm_id' => $farm->id,
                    'espece_id' => $espece->id,
                    'created_at' => Carbon::now()->toIso8601String(),
                    'updated_at' => Carbon::now()->toIso8601String(),
                ],
            ],
            'updated' => [],
            'deleted' => [],
        ],
    ];
    
    // First push with sync_request_id
    $response1 = $this->actingAs($user)
        ->postJson('/api/sync/push', [
            'changes' => $chunk,
            'last_sync_at' => Carbon::now()->subDays(1)->toIso8601String(),
            'farm_id' => $farm->id,
            'sync_request_id' => $syncRequestId,
        ]);
    
    $response1->assertStatus(200);
    $data1 = $response1->json('data');
    expect($data1['cached'])->toBeFalse();
    
    // Second push with same sync_request_id (should return cached)
    $response2 = $this->actingAs($user)
        ->postJson('/api/sync/push', [
            'changes' => $chunk,
            'last_sync_at' => Carbon::now()->subDays(1)->toIso8601String(),
            'farm_id' => $farm->id,
            'sync_request_id' => $syncRequestId,
        ]);
    
    $response2->assertStatus(200);
    $data2 = $response2->json('data');
    expect($data2['cached'])->toBeTrue();
    
    // Results should be identical
    expect($data2['results'])->toBe($data1['results']);
});
