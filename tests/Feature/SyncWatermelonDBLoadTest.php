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
});

test('load test: push 200 items with mixed dependencies', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create(['owner_id' => $user->id]);
    
    // Create reference data
    $espece = Espece::factory()->create();
    $typeEvenement = TypeEvenement::factory()->create(['nom_type' => 'Achat']);
    $categorie = Categorie::factory()->create();
    
    // Generate 100 animals
    $animals = [];
    for ($i = 0; $i < 100; $i++) {
        $animals[] = [
            'id' => Str::uuid(),
            'numero_identification' => 'TEST' . str_pad($i, 3, '0', STR_PAD_LEFT),
            'sexe' => $i % 2 === 0 ? 'MALE' : 'FEMELLE',
            'statut' => 'ACTIF',
            'date_naissance' => Carbon::now()->subYears(rand(1, 5))->toIso8601String(),
            'farm_id' => $farm->id,
            'espece_id' => $espece->id,
            'created_at' => Carbon::now()->toIso8601String(),
            'updated_at' => Carbon::now()->toIso8601String(),
        ];
    }
    
    // Generate 50 evenements (some referencing animals, some not)
    $evenements = [];
    for ($i = 0; $i < 50; $i++) {
        $evenements[] = [
            'id' => Str::uuid(),
            'date_evenement' => Carbon::now()->subDays(rand(1, 30))->toIso8601String(),
            'statut' => 'TERMINE',
            'farm_id' => $farm->id,
            'animal_id' => $i < 40 ? $animals[$i]['id'] : null, // First 40 reference animals
            'type_evenement_id' => $typeEvenement->id,
            'created_at' => Carbon::now()->toIso8601String(),
            'updated_at' => Carbon::now()->toIso8601String(),
        ];
    }
    
    // Generate 50 transactions (with cross dependencies)
    $transactions = [];
    for ($i = 0; $i < 50; $i++) {
        $transactions[] = [
            'id' => Str::uuid(),
            'type_transaction' => $i % 2 === 0 ? 'SORTIE' : 'ENTREE',
            'montant' => rand(1000, 50000),
            'date_transaction' => Carbon::now()->subDays(rand(1, 30))->toIso8601String(),
            'description' => 'Transaction ' . $i,
            'farm_id' => $farm->id,
            'animal_id' => $i < 30 ? $animals[$i]['id'] : null,
            'evenement_id' => $i < 20 ? $evenements[$i]['id'] : null,
            'categorie_id' => $categorie->id,
            'created_at' => Carbon::now()->toIso8601String(),
            'updated_at' => Carbon::now()->toIso8601String(),
        ];
    }
    
    // Create chunk with items in WRONG order to test dependency resolution
    $chunk = [
        'transactions' => [
            'created' => $transactions,
            'updated' => [],
            'deleted' => [],
        ],
        'evenements' => [
            'created' => $evenements,
            'updated' => [],
            'deleted' => [],
        ],
        'animals' => [
            'created' => $animals,
            'updated' => [],
            'deleted' => [],
        ],
    ];
    
    $totalItems = count($animals) + count($evenements) + count($transactions);
    expect($totalItems)->toBe(200);
    
    // Measure response time
    $startTime = microtime(true);
    
    $response = $this->actingAs($user)
        ->postJson('/api/sync/push', [
            'changes' => $chunk,
            'last_sync_at' => Carbon::now()->subDays(1)->toIso8601String(),
            'farm_id' => $farm->id,
        ]);
    
    $endTime = microtime(true);
    $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
    
    $response->assertStatus(200);
    
    $data = $response->json('data');
    
    // Verify all items were processed
    expect($data['results'])->toHaveCount(200);
    
    // Verify no errors
    $errors = collect($data['results'])->filter(fn($r) => $r['status'] === 'error');
    expect($errors)->toHaveCount(0);
    
    // Verify all items exist in database
    expect(Animal::count())->toBe(100);
    expect(Evenement::count())->toBe(50);
    expect(Transaction::count())->toBe(50);
    
    // Verify response time is reasonable (should be < 10 seconds for 200 items)
    expect($responseTime)->toBeLessThan(10000);
    
    // Log performance metrics
    echo "\n=== Load Test Performance Metrics ===\n";
    echo "Total Items: {$totalItems}\n";
    echo "Response Time: " . number_format($responseTime, 2) . "ms\n";
    echo "Items per second: " . number_format($totalItems / ($responseTime / 1000), 2) . "\n";
    echo "====================================\n";
});

test('chunk size limit validation', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create(['owner_id' => $user->id]);
    
    $espece = Espece::factory()->create();
    
    // Create a chunk with 201 items (exceeds limit)
    $animals = [];
    for ($i = 0; $i < 201; $i++) {
        $animals[] = [
            'id' => Str::uuid(),
            'numero_identification' => 'TEST' . str_pad($i, 3, '0', STR_PAD_LEFT),
            'sexe' => 'MALE',
            'statut' => 'ACTIF',
            'farm_id' => $farm->id,
            'espece_id' => $espece->id,
            'created_at' => Carbon::now()->toIso8601String(),
            'updated_at' => Carbon::now()->toIso8601String(),
        ];
    }
    
    $chunk = [
        'animals' => [
            'created' => $animals,
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
    
    $response->assertStatus(413);
    
    $data = $response->json();
    expect($data['message'])->toContain('Chunk trop volumineux');
    expect($data['message'])->toContain('201 items');
    expect($data['message'])->toContain('maximum: 200');
});

test('chunk size exactly at limit (200 items) should succeed', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create(['owner_id' => $user->id]);
    
    $espece = Espece::factory()->create();
    
    // Create a chunk with exactly 200 items
    $animals = [];
    for ($i = 0; $i < 200; $i++) {
        $animals[] = [
            'id' => Str::uuid(),
            'numero_identification' => 'TEST' . str_pad($i, 3, '0', STR_PAD_LEFT),
            'sexe' => 'MALE',
            'statut' => 'ACTIF',
            'farm_id' => $farm->id,
            'espece_id' => $espece->id,
            'created_at' => Carbon::now()->toIso8601String(),
            'updated_at' => Carbon::now()->toIso8601String(),
        ];
    }
    
    $chunk = [
        'animals' => [
            'created' => $animals,
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
    expect($data['results'])->toHaveCount(200);
    expect(Animal::count())->toBe(200);
});
