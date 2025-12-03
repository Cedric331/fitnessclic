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

        $listOfCategories = [
            'Pompes',
            'Abdos',
            'Jambes',
            'Bras',
            'Dos',
            'Cuisses',
            'Triceps',
            'Biceps',
            'Epaules',
            'Fessiers',
        ];

        // Create public categories
        $admin = $adminUsers->first();
        foreach ($listOfCategories as $category) {
            Category::create([
                'name' => $category,
                'type' => 'public',
                'user_id' => $admin->id,
            ]);
        }
    }
}
