<?php

use App\Models\Announcement;
use App\Models\User;

test('unauthenticated user gets null announcement', function () {
    Announcement::factory()->active()->create();

    $response = $this->getJson(route('announcements.current'));

    $response->assertStatus(200);
    $response->assertJson(['announcement' => null]);
});

test('authenticated user gets active announcement', function () {
    $user = User::factory()->create();
    $announcement = Announcement::factory()->active()->create([
        'title' => 'Nouvelle fonctionnalité',
        'description' => 'Description de la nouvelle fonctionnalité',
    ]);

    $response = $this->actingAs($user)->getJson(route('announcements.current'));

    $response->assertStatus(200);
    $response->assertJson([
        'announcement' => [
            'id' => $announcement->id,
            'title' => 'Nouvelle fonctionnalité',
            'description' => 'Description de la nouvelle fonctionnalité',
        ],
    ]);
});

test('authenticated user does not get inactive announcement', function () {
    $user = User::factory()->create();
    Announcement::factory()->create([
        'title' => 'Annonce inactive',
        'is_active' => false,
    ]);

    $response = $this->actingAs($user)->getJson(route('announcements.current'));

    $response->assertStatus(200);
    $response->assertJson(['announcement' => null]);
});

test('authenticated user does not get already seen announcement', function () {
    $user = User::factory()->create();
    $announcement = Announcement::factory()->active()->create();

    // Marquer l'annonce comme vue
    $announcement->markAsSeenBy($user);

    $response = $this->actingAs($user)->getJson(route('announcements.current'));

    $response->assertStatus(200);
    $response->assertJson(['announcement' => null]);
});

test('user can mark announcement as seen', function () {
    $user = User::factory()->create();
    $announcement = Announcement::factory()->active()->create();

    $response = $this->actingAs($user)->postJson(route('announcements.seen', $announcement));

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    expect($announcement->hasBeenSeenBy($user))->toBeTrue();
});

test('marking announcement as seen is idempotent', function () {
    $user = User::factory()->create();
    $announcement = Announcement::factory()->active()->create();

    // Marquer deux fois comme vue
    $this->actingAs($user)->postJson(route('announcements.seen', $announcement));
    $response = $this->actingAs($user)->postJson(route('announcements.seen', $announcement));

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    // Vérifier qu'il n'y a qu'une seule entrée dans la table pivot
    expect($user->seenAnnouncements()->count())->toBe(1);
});

test('unauthenticated user cannot mark announcement as seen', function () {
    $announcement = Announcement::factory()->active()->create();

    $response = $this->postJson(route('announcements.seen', $announcement));

    $response->assertStatus(401);
});

test('activating an announcement deactivates others', function () {
    $announcement1 = Announcement::factory()->active()->create();
    $announcement2 = Announcement::factory()->create();

    expect($announcement1->fresh()->is_active)->toBeTrue();
    expect($announcement2->fresh()->is_active)->toBeFalse();

    // Activer la deuxième annonce
    $announcement2->update(['is_active' => true]);

    expect($announcement1->fresh()->is_active)->toBeFalse();
    expect($announcement2->fresh()->is_active)->toBeTrue();
});

test('only one active announcement at a time', function () {
    Announcement::factory()->active()->create();
    Announcement::factory()->active()->create();
    Announcement::factory()->active()->create();

    $activeCount = Announcement::where('is_active', true)->count();

    expect($activeCount)->toBe(1);
});

