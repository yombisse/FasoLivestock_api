<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\Farm;
use App\Models\Espece;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Animal>
 */
class AnimalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Animal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'espece_id' => Espece::factory(),
            'nom' => fake()->name(),
            'numero_identification' => 'ANI-' . fake()->unique()->randomNumber(8),
            'sexe' => fake()->randomElement(['male', 'femelle']),
            'date_naissance' => fake()->date(),
            'statut' => fake()->randomElement(['ACTIF', 'VENDU', 'MORT', 'PERDU']),
            'photo' => null,
            'mother_id' => null,
            'naissance_id' => null,
            'sync_status' => 'synced',
            'last_modified_by' => null,
            'version' => 1,
        ];
    }
}
