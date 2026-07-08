<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Farm>
 */
class FarmFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Farm::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'location' => fake()->city(),
            'description' => fake()->text(),
            'type_elevage' => fake()->randomElement(['bovin', 'ovin', 'caprin', 'porcin', 'volaille']),
            'owner_id' => User::factory(),
            'sync_status' => 'synced',
            'last_modified_by' => null,
            'version' => 1,
        ];
    }
}
