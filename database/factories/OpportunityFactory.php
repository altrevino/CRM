<?php

namespace Database\Factories;

use App\Enums\PipelineStage;
use App\Models\Opportunity;
use App\Models\Ranch;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Opportunity> */
class OpportunityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ranch_id' => Ranch::factory(),
            'stage' => PipelineStage::Prospect,
            'service_type' => fake()->randomElement(['completo', 'representativo', 'localizacion']),
            'quoted_hectares' => fake()->numberBetween(300, 3000),
        ];
    }

    public function stage(PipelineStage $stage): static
    {
        return $this->state(fn () => ['stage' => $stage]);
    }
}
