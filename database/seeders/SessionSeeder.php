<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Exercise;
use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Seeder;

class SessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get customer users (only customers create sessions)
        $clientUsers = User::where('role', UserRole::CUSTOMER->value)->get();

        if ($clientUsers->isEmpty()) {
            $clientUsers = User::factory()->count(5)->customer()->create();
        }

        // Get customers
        $customers = Customer::all();

        if ($customers->isEmpty()) {
            // Create customers for each client user
            foreach ($clientUsers as $user) {
                Customer::factory()
                    ->count(3)
                    ->for($user)
                    ->create();
            }
            $customers = Customer::all();
        }

        // Get available exercises (shared or owned by the user)
        $exercises = Exercise::all();

        if ($exercises->isEmpty()) {
            $this->command->warn('No exercises found. Please run ExerciseSeeder first.');

            return;
        }

        // Create sessions for each client user
        foreach ($clientUsers as $user) {
            // Get customers for this user
            $userCustomers = $customers->where('user_id', $user->id);

            if ($userCustomers->isEmpty()) {
                continue;
            }

            // Create 2-5 sessions per customer
            foreach ($userCustomers as $customer) {
                $sessions = Session::factory()
                    ->count(fake()->numberBetween(2, 5))
                    ->for($user)
                    ->for($customer)
                    ->create();

                // Attach exercises to each session
                foreach ($sessions as $session) {
                    $sessionExercises = $exercises->random(fake()->numberBetween(3, 8));

                    $order = 1;
                    foreach ($sessionExercises as $exercise) {
                        $session->exercises()->attach($exercise->id, [
                            'repetitions' => fake()->optional()->numberBetween(5, 20),
                            'rest_time' => fake()->optional()->randomElement(['30s', '1min', '2min', '3min']),
                            'duration' => fake()->optional()->randomElement(['30s', '1min', '2min']),
                            'additional_description' => fake()->optional()->sentence(),
                            'order' => $order++,
                        ]);
                    }
                }
            }
        }
    }
}
