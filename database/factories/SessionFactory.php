<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Session>
 */
class SessionFactory extends Factory
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
            'client_id' => Customer::factory(),
            'name' => fake()->optional()->words(3, true),
            'notes' => fake()->optional()->paragraph(),
            'session_date' => fake()->optional()->dateTimeBetween('-1 year', '+1 month'),
        ];
    }

    /**
     * Indicate that the session has a name.
     */
    public function named(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->words(3, true),
        ]);
    }

    /**
     * Indicate that the session has a date.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'session_date' => fake()->dateTimeBetween('now', '+1 month'),
        ]);
    }
}
