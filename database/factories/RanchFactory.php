<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Ranch;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ranch> */
class RanchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => 'Rancho '.fake()->unique()->lastName(),
            'municipality' => fake()->randomElement(['Anáhuac', 'Lampazos de Naranjo', 'Galeana', 'Linares']),
            'total_hectares' => fake()->numberBetween(500, 5000),
            'fence_type' => fake()->randomElement(['alta', 'baja']),
            'km_round_trip' => fake()->numberBetween(100, 700),
        ];
    }
}
