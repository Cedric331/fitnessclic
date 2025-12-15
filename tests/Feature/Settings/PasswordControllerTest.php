<?php

use App\Models\User;

test('unauthenticated user cannot access password settings', function () {
    $response = $this->get(route('user-password.edit'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view password settings', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('user-password.edit'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Password')
    );
});

test('user can update password with valid current password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect();
    $this->assertTrue(\Hash::check('new-password', $user->fresh()->password));
});

test('user cannot update password with invalid current password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors('current_password');
    $this->assertTrue(\Hash::check('password', $user->fresh()->password));
});

test('user cannot update password without confirmation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors('password');
});

test('user cannot update password with weak password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => '123',
        'password_confirmation' => '123',
    ]);

    $response->assertSessionHasErrors('password');
});
