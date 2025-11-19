<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'type' => 'private',
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the category is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'public',
            // user_id can be null or admin ID for public categories
        ]);
    }

    /**
     * Indicate that the category is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'private',
            'user_id' => User::factory(),
        ]);
    }
}
