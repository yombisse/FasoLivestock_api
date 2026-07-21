<?php

namespace Database\Factories;

use App\Models\Espece;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Espece>
 */
class EspeceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Espece::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => substr(Str::random(20), 0, 20),
            'nom' => fake()->randomElement(['Bovin', 'Ovin', 'Caprin', 'Porcin', 'Volaille', 'Lapin', 'Equin']),
            'description' => fake()->text(),
        ];
    }
}
