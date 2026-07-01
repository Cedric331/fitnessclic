<?php

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new coaches are redirected to their profile management after registration', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test Coach',
        'email' => 'coach@gmail.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('coach.profile.edit', absolute: false));
});

test('new clients keep the default registration redirect', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test Client',
        'email' => 'client@gmail.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'client',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('sessions.create', absolute: false));
});
