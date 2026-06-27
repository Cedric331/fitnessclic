<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CoachSeeder extends Seeder
{
    /**
     * Number of coaches to generate.
     */
    private const COUNT = 100;

    private array $cities = [
        ['Paris', '75001'], ['Lyon', '69002'], ['Marseille', '13001'],
        ['Toulouse', '31000'], ['Bordeaux', '33000'], ['Nantes', '44000'],
        ['Lille', '59000'], ['Nice', '06000'], ['Strasbourg', '67000'],
        ['Montpellier', '34000'], ['Rennes', '35000'], ['Grenoble', '38000'],
        ['Aix-en-Provence', '13100'], ['Annecy', '74000'], ['Tours', '37000'],
    ];

    private array $specialties = [
        'Musculation', 'Cardio', 'Cross-training', 'Yoga', 'Pilates',
        'Perte de poids', 'Préparation physique', 'Remise en forme', 'Boxe',
        'Course à pied', 'Renforcement musculaire', 'Mobilité', 'Nutrition',
        'HIIT', 'Stretching', 'Coaching à domicile',
    ];

    public function run(): void
    {
        $faker = fake('fr_FR');

        for ($i = 1; $i <= self::COUNT; $i++) {
            $email = "coach{$i}@demo.fitnessclic.fr";

            // Idempotent: skip coaches already seeded.
            if (User::where('email', $email)->exists()) {
                continue;
            }

            $name = $faker->name();

            $user = User::factory()->coach()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                // Avatar de démo (portrait déterministe par email).
                'avatar_url' => 'https://i.pravatar.cc/400?u='.urlencode($email),
            ]);

            $specialties = $faker->randomElements(
                $this->specialties,
                $faker->numberBetween(1, 4)
            );
            [$city, $postalCode] = $faker->randomElement($this->cities);

            CoachProfile::create([
                'user_id' => $user->id,
                'headline' => 'Coach sportif spécialisé en '.strtolower($specialties[0]),
                'bio' => $faker->paragraphs($faker->numberBetween(2, 3), true),
                'hourly_rate' => $faker->numberBetween(20, 90) * 100, // centimes
                'city' => $city,
                'postal_code' => $postalCode,
                'specialties' => $specialties,
                'is_published' => true,
            ]);
        }

        $this->command?->info(self::COUNT.' coachs de démonstration générés (ou déjà présents).');
    }
}
