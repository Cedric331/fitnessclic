<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all customer users
        $clientUsers = User::where('role', UserRole::CUSTOMER->value)->get();

        if ($clientUsers->isEmpty()) {
            // Create some customer users first if none exist
            $clientUsers = User::factory()->count(5)->customer()->create();
        }

        // Create 3-5 customers for each client user
        foreach ($clientUsers as $user) {
            Customer::factory()
                ->count(fake()->numberBetween(3, 5))
                ->for($user)
                ->create();
        }
    }
}
