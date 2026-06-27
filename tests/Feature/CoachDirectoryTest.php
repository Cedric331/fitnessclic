<?php

use App\Models\CoachProfile;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

// Inertia full-page renders resolve the Vite manifest; disable Vite so these
// HTTP tests don't depend on a production build being present.
beforeEach(fn () => $this->withoutVite());

it('lists only published coaches in the public directory', function () {
    $publishedCoach = User::factory()->coach()->create(['name' => 'Jean Coach']);
    CoachProfile::create([
        'user_id' => $publishedCoach->id,
        'headline' => 'Coach muscu',
        'city' => 'Paris',
        'is_published' => true,
    ]);

    $hiddenCoach = User::factory()->coach()->create(['name' => 'Secret Coach']);
    CoachProfile::create([
        'user_id' => $hiddenCoach->id,
        'city' => 'Lyon',
        'is_published' => false,
    ]);

    get('/coachs')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('coachs/Index')
            ->where('coaches.total', 1)
            ->where('coaches.data.0.name', 'Jean Coach')
        );
});

it('shows a published coach profile and 404s an unpublished one', function () {
    $coach = User::factory()->coach()->create(['name' => 'Alice Coach']);
    $profile = CoachProfile::create([
        'user_id' => $coach->id,
        'city' => 'Paris',
        'is_published' => true,
    ]);

    get("/coachs/{$profile->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('coachs/Show')
            ->where('coach.name', 'Alice Coach')
        );

    $hidden = User::factory()->coach()->create();
    $hiddenProfile = CoachProfile::create([
        'user_id' => $hidden->id,
        'is_published' => false,
    ]);

    get("/coachs/{$hiddenProfile->slug}")->assertNotFound();
});

it('lets a coach create and publish their profile', function () {
    $coach = User::factory()->coach()->create();

    actingAs($coach)
        ->post('/mon-profil-coach', [
            'headline' => 'Coach perso',
            'bio' => 'Ma description',
            'hourly_rate' => '45',
            'city' => 'Marseille',
            'specialties' => 'Cardio, Muscu, Cardio',
            'is_published' => true,
        ])
        ->assertRedirect(route('coach.profile.edit'));

    $profile = $coach->fresh()->coachProfile;

    expect($profile)->not->toBeNull()
        ->and($profile->is_published)->toBeTrue()
        ->and($profile->hourly_rate)->toBe(4500) // 45 € stored as cents
        ->and($profile->city)->toBe('Marseille')
        ->and($profile->specialties)->toBe(['Cardio', 'Muscu']); // trimmed + deduped
});

it('requires a city to publish', function () {
    $coach = User::factory()->coach()->create();

    actingAs($coach)
        ->from('/mon-profil-coach')
        ->post('/mon-profil-coach', [
            'is_published' => true,
            'city' => '',
        ])
        ->assertRedirect('/mon-profil-coach')
        ->assertSessionHasErrors('city');
});

it('redirects a client account away from the coach profile editor', function () {
    $client = User::factory()->client()->create();

    actingAs($client)->get('/mon-profil-coach')->assertRedirect(route('client.space.index'));
    actingAs($client)->post('/mon-profil-coach', ['city' => 'X'])->assertRedirect(route('client.space.index'));
});

it('redirects guests away from the coach profile editor', function () {
    get('/mon-profil-coach')->assertRedirect(route('login'));
});

it('filters the directory by price range', function () {
    $cheapCoach = User::factory()->coach()->create(['name' => 'Cheap Coach']);
    CoachProfile::create(['user_id' => $cheapCoach->id, 'city' => 'Paris', 'hourly_rate' => 2000, 'is_published' => true]);

    $priceyCoach = User::factory()->coach()->create(['name' => 'Pricey Coach']);
    CoachProfile::create(['user_id' => $priceyCoach->id, 'city' => 'Paris', 'hourly_rate' => 8000, 'is_published' => true]);

    get('/coachs?min_rate=50')
        ->assertInertia(fn ($page) => $page
            ->where('coaches.total', 1)
            ->where('coaches.data.0.name', 'Pricey Coach'));

    get('/coachs?max_rate=50')
        ->assertInertia(fn ($page) => $page
            ->where('coaches.total', 1)
            ->where('coaches.data.0.name', 'Cheap Coach'));
});

it('serves an Open Graph share view to social crawlers', function () {
    $coach = User::factory()->coach()->create(['name' => 'OG Coach']);
    $profile = CoachProfile::create(['user_id' => $coach->id, 'headline' => 'Super coach', 'city' => 'Paris', 'is_published' => true]);

    $this->withHeaders(['User-Agent' => 'facebookexternalhit/1.1'])
        ->get("/coachs/{$profile->slug}")
        ->assertOk()
        ->assertSee('og:type', false)
        ->assertSee('twitter:card', false)
        ->assertSee('OG Coach', false);
});
