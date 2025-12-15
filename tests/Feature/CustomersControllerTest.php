<?php

use App\Models\Customer;
use App\Models\Session;
use App\Models\User;
use Laravel\Cashier\Subscription;

test('unauthenticated user cannot access customers index', function () {
    $response = $this->get(route('client.customers.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view own customers', function () {
    $user = User::factory()->create();
    Customer::factory()->count(3)->for($user)->create();

    $response = $this->actingAs($user)->get(route('client.customers.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('clients/Index')
        ->has('customers.data', 3)
    );
});

test('customers index filters by search term', function () {
    $user = User::factory()->create();
    Customer::factory()->for($user)->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
    ]);
    Customer::factory()->for($user)->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);

    $response = $this->actingAs($user)->get(route('client.customers.index', ['search' => 'John']));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('filters.search', 'John')
        ->has('customers.data', 1)
    );
});

test('customers index paginates results', function () {
    $user = User::factory()->create();
    Customer::factory()->count(15)->for($user)->create();

    $response = $this->actingAs($user)->get(route('client.customers.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('customers.data', 12)
        ->where('customers.per_page', 12)
    );
});

test('free user cannot create customer', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('client.customers.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
    ]);

    $response->assertRedirect(route('client.customers.index'));
    $response->assertSessionHas('error', 'La création de clients est réservée aux abonnés Pro. Passez à Pro pour créer des clients illimités.');
    $this->assertDatabaseMissing('customers', ['email' => 'john@example.com']);
});

test('pro user can create customer', function () {
    $user = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
    ]);

    $response = $this->actingAs($user)->post(route('client.customers.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '0123456789',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('client.customers.index'));
    $response->assertSessionHas('success', 'Client créé avec succès.');
    $this->assertDatabaseHas('customers', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'user_id' => $user->id,
        'is_active' => true,
    ]);
});

test('authenticated user can view own customer', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->for($user)->create();
    Session::factory()->count(3)->for($user)->create()->each(function ($session) use ($customer) {
        $session->customers()->attach($customer);
    });

    $response = $this->actingAs($user)->get(route('client.customers.show', $customer));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('clients/Show')
        ->has('customer')
        ->has('training_sessions', 3)
    );
});

test('user cannot view another user customer', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $customer = Customer::factory()->for($otherUser)->create();

    $response = $this->actingAs($user)->get(route('client.customers.show', $customer));

    $response->assertRedirect(route('client.customers.index'));
    $response->assertSessionHas('error', 'Vous n\'avez pas les permissions pour voir ce client.');
});

test('user can update own customer', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->for($user)->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $response = $this->actingAs($user)->put(route('client.customers.update', $customer), [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
    ]);

    $response->assertRedirect(route('client.customers.index'));
    $response->assertSessionHas('success', 'Client modifié avec succès.');
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
    ]);
});

test('user can delete own customer', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->for($user)->create();

    $response = $this->actingAs($user)->delete(route('client.customers.destroy', $customer));

    $response->assertRedirect(route('client.customers.index'));
    $response->assertSessionHas('success', 'Client supprimé avec succès.');
    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
});

test('user cannot delete another user customer', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $customer = Customer::factory()->for($otherUser)->create();

    $response = $this->actingAs($user)->delete(route('client.customers.destroy', $customer));

    $response->assertRedirect(route('client.customers.index'));
    $response->assertSessionHas('error', 'Vous n\'avez pas les permissions pour supprimer ce client.');
    $this->assertDatabaseHas('customers', ['id' => $customer->id]);
});

