<?php

use App\Models\User;
use Laravel\Cashier\Subscription;

test('unauthenticated user cannot access subscription page', function () {
    $response = $this->get(route('subscription.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view subscription page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('subscription.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('subscription/Index')
        ->has('hasActiveSubscription')
        ->has('onTrial')
        ->has('subscriptionStatus')
    );
});

test('subscription page shows active subscription status', function () {
    $user = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
    ]);

    $response = $this->actingAs($user)->get(route('subscription.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('hasActiveSubscription', true)
        ->where('subscriptionStatus', 'active')
    );
});

test('subscription page shows trial information', function () {
    $user = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'trialing',
        'trial_ends_at' => now()->addDays(7),
    ]);

    $response = $this->actingAs($user)->get(route('subscription.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('onTrial', true)
        ->where('subscriptionStatus', 'trialing')
    );
});

test('subscription page shows cancellation information', function () {
    $user = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
        'ends_at' => now()->addDays(10),
    ]);

    $response = $this->actingAs($user)->get(route('subscription.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('isCancelling', true)
    );
});

test('authenticated user can access checkout', function () {
    $user = User::factory()->create();
    config(['cashier.price_id' => 'price_test123']);

    $response = $this->actingAs($user)->get(route('subscription.checkout'));

    // La réponse peut être une redirection vers Stripe ou une vue de redirection
    $response->assertStatus(302);
});

test('checkout redirects with error if price id not configured', function () {
    $user = User::factory()->create();
    config(['cashier.price_id' => null]);

    $response = $this->actingAs($user)->get(route('subscription.checkout'));

    $response->assertRedirect(route('subscription.index'));
    $response->assertSessionHas('error', 'Configuration Stripe manquante. Veuillez contacter le support.');
});

test('authenticated user can access billing portal', function () {
    $user = User::factory()->create();
    $user->createAsStripeCustomer();

    $response = $this->actingAs($user)->get(route('subscription.portal'));

    // La réponse peut être une redirection vers Stripe ou une vue de redirection
    $response->assertStatus(200);
});

test('billing portal redirects with error if user has no stripe id', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('subscription.portal'));

    $response->assertRedirect(route('subscription.index'));
    $response->assertSessionHas('error', 'Vous devez d\'abord créer un compte Stripe.');
});

test('user can access success page after checkout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('subscription.success'));

    $response->assertRedirect(route('subscription.index'));
    $response->assertSessionHas('success', 'Votre abonnement a été activé avec succès !');
});
