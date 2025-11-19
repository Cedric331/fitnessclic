<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Exercise;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin users
        $adminUsers = User::where('role', UserRole::ADMIN->value)->get();

        if ($adminUsers->isEmpty()) {
            $adminUsers = User::factory()->count(2)->admin()->create();
        }

        // Create shared exercises by admins (automatically shared)
        foreach ($adminUsers as $admin) {
            Exercise::factory()
                ->count(fake()->numberBetween(10, 20))
                ->for($admin)
                ->shared() // Admin exercises are always shared
                ->create();
        }

        // Get customer users
        $clientUsers = User::where('role', UserRole::CUSTOMER->value)->get();

        if ($clientUsers->isEmpty()) {
            $clientUsers = User::factory()->count(5)->customer()->create();
        }

        // Create exercises for customers (some shared, some private)
        foreach ($clientUsers as $user) {
            // Create 5-10 private exercises
            Exercise::factory()
                ->count(fake()->numberBetween(5, 10))
                ->for($user)
                ->private()
                ->create();

            // Create 2-5 shared exercises
            Exercise::factory()
                ->count(fake()->numberBetween(2, 5))
                ->for($user)
                ->shared()
                ->create();
        }

        // Attach exercises to categories
        $categories = Category::all();
        $exercises = Exercise::all();

        if ($categories->isNotEmpty() && $exercises->isNotEmpty()) {
            foreach ($exercises->random(min(20, $exercises->count())) as $exercise) {
                // Attach 1-3 random categories to each exercise
                $exercise->categories()->attach(
                    $categories->random(fake()->numberBetween(1, 3))->pluck('id')->toArray()
                );
            }
        }
    }
}
