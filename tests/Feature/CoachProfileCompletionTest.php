<?php

use App\Models\CoachProfile;
use App\Models\User;

use function Pest\Laravel\actingAs;

// Inertia full-page renders resolve the Vite manifest; disable Vite so these
// HTTP tests don't depend on a production build being present.
beforeEach(fn () => $this->withoutVite());

test('completion is empty for a blank profile', function () {
    $coach = User::factory()->coach()->create();
    $profile = CoachProfile::create(['user_id' => $coach->id]);

    $completion = $profile->completion();

    expect($completion['completed'])->toBe(0);
    expect($completion['total'])->toBe(5);
    expect($completion['percentage'])->toBe(0);
    expect($completion['is_complete'])->toBeFalse();
});

test('completion counts filled fields including city (photo excluded without upload)', function () {
    $coach = User::factory()->coach()->create();
    $profile = CoachProfile::create([
        'user_id' => $coach->id,
        'bio' => 'Ma présentation détaillée.',
        'specialties' => ['Musculation', 'Cardio'],
        'hourly_rate' => 4000,
        'city' => 'Paris',
    ]);

    $completion = $profile->completion();

    // bio + specialties + hourly_rate + city = 4/5, photo manquante.
    expect($completion['completed'])->toBe(4);
    expect($completion['percentage'])->toBe(80);
    expect($completion['is_complete'])->toBeFalse();

    $photoItem = collect($completion['items'])->firstWhere('key', 'photo');
    expect($photoItem['done'])->toBeFalse();

    $locationItem = collect($completion['items'])->firstWhere('key', 'location');
    expect($locationItem['done'])->toBeTrue();
});

test('the edit page exposes the profile completion', function () {
    $coach = User::factory()->coach()->create();

    actingAs($coach)->get(route('coach.profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('coach/ProfileEdit')
            ->has('profile.completion.items', 5)
            ->where('profile.completion.percentage', 0)
        );
});

test('the coach profile completion is shared to the frontend', function () {
    $coach = User::factory()->coach()->create();
    CoachProfile::create([
        'user_id' => $coach->id,
        'bio' => 'Bio',
        'specialties' => ['Yoga'],
        'hourly_rate' => 5000,
        'city' => 'Lyon',
    ]);

    // bio + specialties + hourly_rate + city = 4/5 = 80 %.
    actingAs($coach)->get(route('coach.profile.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.profileCompletion.percentage', 80)
            ->where('auth.user.profileCompletion.is_complete', false)
        );
});

test('a coach can set their coaching mode', function () {
    $coach = User::factory()->coach()->create();

    actingAs($coach)->post(route('coach.profile.update'), [
        'coaching_mode' => 'both',
    ])->assertRedirect(route('coach.profile.edit'));

    $profile = CoachProfile::where('user_id', $coach->id)->firstOrFail();
    expect($profile->coaching_mode->value)->toBe('both');
});

test('coaching mode defaults to in person', function () {
    $coach = User::factory()->coach()->create();
    $profile = CoachProfile::create(['user_id' => $coach->id]);

    expect($profile->fresh()->coaching_mode->value)->toBe('in_person');
});

test('non coaches do not get a profile completion summary', function () {
    $client = User::factory()->client()->create();

    actingAs($client)->get(route('client.space.index'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.profileCompletion', null)
        );
});
