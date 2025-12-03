<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@test.fr'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN->value,
                'email_verified_at' => now(),
            ]
        );

        // Create customer user
        User::firstOrCreate(
            ['email' => 'test@test.fr'],
            [
                'name' => 'Customer User',
                'password' => Hash::make('password'),
                'role' => UserRole::CUSTOMER->value,
                'email_verified_at' => now(),
            ]
        );

        // Run other seeders
        $this->call([
            CustomerSeeder::class,
            CategorySeeder::class,
            // ExerciseSeeder::class,
            // SessionSeeder::class,
        ]);
    }
}
