<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create public categories (created by admins)
        $adminUsers = User::where('role', UserRole::ADMIN->value)->get();

        if ($adminUsers->isEmpty()) {
            // If no admin exists, create one for seeding
            $admin = User::factory()->admin()->create();
            $adminUsers = collect([$admin]);
        }

        // Create 5-10 public categories
        $admin = $adminUsers->first();
        Category::factory()
            ->count(fake()->numberBetween(5, 10))
            ->public()
            ->create([
                'user_id' => $admin->id, // Can be null or admin ID for public categories
            ]);

        // Create private categories for customer users
        $clientUsers = User::where('role', UserRole::CUSTOMER->value)->get();

        if ($clientUsers->isEmpty()) {
            $clientUsers = User::factory()->count(5)->customer()->create();
        }

        // Create 2-4 private categories for each client
        foreach ($clientUsers as $user) {
            Category::factory()
                ->count(fake()->numberBetween(2, 4))
                ->private()
                ->for($user)
                ->create();
        }
    }
}
