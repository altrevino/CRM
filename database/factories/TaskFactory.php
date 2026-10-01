<?php

namespace Database\Factories;

use App\Models\Opportunity;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Dar seguimiento',
            'due_date' => today()->addDays(3),
            'opportunity_id' => Opportunity::factory(),
        ];
    }
}
