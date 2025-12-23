<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->optional(0.7)->text(300),
            'status' => $this->faker->randomElement(['todo', 'doing', 'done']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'due_date' => $this->faker->optional()->date('Y-m-d'),
            'estimate_minutes' => $this->faker->optional()->numberBetween(0, 8 * 60),
        ];
    }
}
