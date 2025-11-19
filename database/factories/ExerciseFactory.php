<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exercise>
 */
class ExerciseFactory extends Factory
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
            'title' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'suggested_duration' => fake()->optional()->randomElement(['30s', '1min', '2min', '3min', '5min', '10min']),
            'is_shared' => false,
        ];
    }

    /**
     * Indicate that the exercise is shared.
     */
    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_shared' => true,
        ]);
    }

    /**
     * Indicate that the exercise is not shared.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_shared' => false,
        ]);
    }
}
