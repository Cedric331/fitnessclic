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

        User::firstOrCreate(
            ['email' => 'customer1@test.fr'],
            [
                'name' => 'Customer 1 User',
                'password' => Hash::make('password'),
                'role' => UserRole::COACH->value,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'customer2@test.fr'],
            [
                'name' => 'Customer 2 User',
                'password' => Hash::make('password'),
                'role' => UserRole::COACH->value,
                'email_verified_at' => now(),
            ]
        );

        // Run other seeders
        $this->call([
            // CustomerSeeder::class,
            // CategorySeeder::class,
            // ExerciseSeeder::class,
            // SessionSeeder::class,
            // CoachSeeder::class, // 100 coachs de démo : php artisan db:seed --class=CoachSeeder
        ]);
    }
}
