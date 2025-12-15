<?php

use App\Models\User;

test('unauthenticated user cannot access profile settings', function () {
    $response = $this->get(route('profile.edit'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view profile settings', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Profile')
        ->has('mustVerifyEmail')
    );
});

test('user can update profile information', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $response->assertRedirect(route('profile.edit'));
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
});

test('user email verification is reset when email changes', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => 'newemail@example.com',
    ]);

    $response->assertRedirect(route('profile.edit'));
    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('user can delete own profile with correct password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    $response->assertRedirect('/');
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $this->assertGuest();
});

test('user cannot delete profile with wrong password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertDatabaseHas('users', ['id' => $user->id]);
    $this->assertAuthenticatedAs($user);
});

test('user session is invalidated after profile deletion', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    $response->assertRedirect('/');
    $this->assertGuest();
});
