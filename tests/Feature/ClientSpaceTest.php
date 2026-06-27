<?php

use App\Models\Customer;
use App\Models\Session;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(fn () => $this->withoutVite());

it('shows a client only the courses assigned to their linked records', function () {
    $client = User::factory()->client()->create(['email' => 'client@x.fr']);
    $coach = User::factory()->coach()->create(['name' => 'Coach A']);

    $record = Customer::create([
        'user_id' => $coach->id,
        'account_user_id' => $client->id,
        'first_name' => 'Client',
        'last_name' => 'X',
        'email' => 'client@x.fr',
    ]);

    $mine = Session::create(['user_id' => $coach->id, 'name' => 'Ma séance']);
    $mine->customers()->attach($record->id);

    // A session assigned to someone else must not appear.
    $other = User::factory()->client()->create(['email' => 'other@x.fr']);
    $otherRecord = Customer::create([
        'user_id' => $coach->id,
        'account_user_id' => $other->id,
        'first_name' => 'Other',
        'last_name' => 'Y',
        'email' => 'other@x.fr',
    ]);
    $hidden = Session::create(['user_id' => $coach->id, 'name' => 'Pas la mienne']);
    $hidden->customers()->attach($otherRecord->id);

    actingAs($client)
        ->get('/espace-client')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('client/Dashboard')
            ->has('courses', 1)
            ->where('courses.0.name', 'Ma séance')
            ->where('courses.0.coach_name', 'Coach A')
            ->has('coaches', 1)
        );
});

it('aggregates courses and coaches across several coaches', function () {
    $client = User::factory()->client()->create(['email' => 'multi@x.fr']);

    $coachA = User::factory()->coach()->create(['name' => 'Coach A']);
    $recordA = Customer::create(['user_id' => $coachA->id, 'account_user_id' => $client->id, 'first_name' => 'M', 'last_name' => 'X', 'email' => 'multi@x.fr']);
    Session::create(['user_id' => $coachA->id, 'name' => 'Séance A'])->customers()->attach($recordA->id);

    $coachB = User::factory()->coach()->create(['name' => 'Coach B']);
    $recordB = Customer::create(['user_id' => $coachB->id, 'account_user_id' => $client->id, 'first_name' => 'M', 'last_name' => 'X', 'email' => 'multi@x.fr']);
    Session::create(['user_id' => $coachB->id, 'name' => 'Séance B'])->customers()->attach($recordB->id);

    actingAs($client)
        ->get('/espace-client')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('client/Dashboard')
            ->has('courses', 2)
            ->has('coaches', 2)
        );
});

it('forbids a coach from the client space', function () {
    $coach = User::factory()->coach()->create();
    actingAs($coach)->get('/espace-client')->assertForbidden();
});

it('redirects the dashboard by role', function () {
    $client = User::factory()->client()->create();
    actingAs($client)->get('/dashboard')->assertRedirect(route('client.space.index'));

    $coach = User::factory()->coach()->create();
    actingAs($coach)->get('/dashboard')->assertRedirect(route('sessions.create'));
});

it('redirects guests away from the client space', function () {
    get('/espace-client')->assertRedirect(route('login'));
});

it('blocks client accounts from coach-only routes but allows shared ones', function () {
    $client = User::factory()->client()->create();

    // Coach-only routes → redirected to the client space.
    foreach (['/sessions', '/customers', '/exercises', '/categories', '/team', '/subscription'] as $url) {
        actingAs($client)->get($url)->assertRedirect(route('client.space.index'));
    }

    // Shared routes remain accessible to clients.
    actingAs($client)->get('/espace-client')->assertOk();
    actingAs($client)->get('/messages')->assertOk();
    // A client can also browse the public coach directory to find a coach.
    actingAs($client)->get('/coachs')->assertOk();
});

it('lets coaches and admins reach coach-only routes', function () {
    $coach = User::factory()->coach()->create();
    actingAs($coach)->get('/customers')->assertOk();

    $admin = User::factory()->admin()->create();
    actingAs($admin)->get('/customers')->assertOk();
});
