<?php

use App\Enums\UserRole;
use App\Mail\CustomerInvitationMail;
use App\Models\Customer;
use App\Models\CustomerInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;

function makeProCoach(): User
{
    $user = User::factory()->create();
    Subscription::factory()->create([
        'user_id' => $user->id,
        'stripe_status' => 'active',
    ]);

    return $user->refresh();
}

test('free coach cannot invite a client', function () {
    Mail::fake();
    $coach = User::factory()->create();
    $customer = Customer::factory()->for($coach)->create(['email' => 'client@gmail.com']);

    $response = $this->actingAs($coach)->post(route('client.customers.invite', $customer));

    $response->assertRedirect(route('client.customers.show', $customer));
    $response->assertSessionHas('error');
    $this->assertDatabaseCount('customer_invitations', 0);
    Mail::assertNothingSent();
});

test('pro coach can invite a client with an email', function () {
    Mail::fake();
    $coach = makeProCoach();
    $customer = Customer::factory()->for($coach)->create(['email' => 'client@gmail.com']);

    $response = $this->actingAs($coach)->post(route('client.customers.invite', $customer));

    $response->assertRedirect(route('client.customers.show', $customer));
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('customer_invitations', [
        'customer_id' => $customer->id,
        'invited_by_user_id' => $coach->id,
        'email' => 'client@gmail.com',
        'accepted_at' => null,
    ]);
    Mail::assertSent(CustomerInvitationMail::class);
});

test('coach cannot invite a client without an email', function () {
    Mail::fake();
    $coach = makeProCoach();
    $customer = Customer::factory()->for($coach)->create(['email' => null]);

    $response = $this->actingAs($coach)->post(route('client.customers.invite', $customer));

    $response->assertSessionHas('error');
    $this->assertDatabaseCount('customer_invitations', 0);
    Mail::assertNothingSent();
});

test('coach cannot invite a client already linked to an account', function () {
    Mail::fake();
    $coach = makeProCoach();
    $account = User::factory()->create(['role' => UserRole::CLIENT, 'email' => 'linked@gmail.com']);
    $customer = Customer::factory()->for($coach)->create([
        'email' => 'linked@gmail.com',
        'account_user_id' => $account->id,
    ]);

    $response = $this->actingAs($coach)->post(route('client.customers.invite', $customer));

    $response->assertSessionHas('error');
    $this->assertDatabaseCount('customer_invitations', 0);
    Mail::assertNothingSent();
});

test('coach cannot invite another coachs client', function () {
    $coach = makeProCoach();
    $other = User::factory()->create();
    $customer = Customer::factory()->for($other)->create(['email' => 'foreign@gmail.com']);

    $response = $this->actingAs($coach)->post(route('client.customers.invite', $customer));

    $response->assertForbidden();
});

test('authenticated client accepts an invitation and gets linked', function () {
    $coach = User::factory()->create();
    $customer = Customer::factory()->for($coach)->create(['email' => 'client@gmail.com']);
    $invitation = CustomerInvitation::create([
        'customer_id' => $customer->id,
        'invited_by_user_id' => $coach->id,
        'email' => 'client@gmail.com',
        'token' => Str::uuid()->toString(),
        'expires_at' => now()->addHours(48),
    ]);
    $client = User::factory()->create(['role' => UserRole::CLIENT, 'email' => 'client@gmail.com']);

    $response = $this->actingAs($client)->post(route('customers.invitations.accept', $invitation->token));

    $response->assertRedirect(route('client.space.index'));
    expect($customer->fresh()->account_user_id)->toBe($client->id);
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
    expect($invitation->fresh()->invited_user_id)->toBe($client->id);
});

test('accepting with a mismatched email is rejected', function () {
    $coach = User::factory()->create();
    $customer = Customer::factory()->for($coach)->create(['email' => 'client@gmail.com']);
    $invitation = CustomerInvitation::create([
        'customer_id' => $customer->id,
        'invited_by_user_id' => $coach->id,
        'email' => 'client@gmail.com',
        'token' => Str::uuid()->toString(),
        'expires_at' => now()->addHours(48),
    ]);
    $other = User::factory()->create(['role' => UserRole::CLIENT, 'email' => 'someone-else@gmail.com']);

    $this->actingAs($other)->post(route('customers.invitations.accept', $invitation->token))
        ->assertSessionHas('error');

    expect($customer->fresh()->account_user_id)->toBeNull();
});

test('registering through a customer invitation forces the client role and links the customer', function () {
    $coach = User::factory()->create();
    $customer = Customer::factory()->for($coach)->create(['email' => 'newclient@gmail.com']);
    $invitation = CustomerInvitation::create([
        'customer_id' => $customer->id,
        'invited_by_user_id' => $coach->id,
        'email' => 'newclient@gmail.com',
        'token' => Str::uuid()->toString(),
        'expires_at' => now()->addHours(48),
    ]);

    $response = $this->post(route('register.store'), [
        'name' => 'New Client',
        'email' => 'newclient@gmail.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'customer_invite' => $invitation->token,
    ]);

    $this->assertAuthenticated();
    $newUser = User::where('email', 'newclient@gmail.com')->firstOrFail();
    expect($newUser->role)->toBe(UserRole::CLIENT);
    expect($customer->fresh()->account_user_id)->toBe($newUser->id);
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('combined create and invite sends an invitation for a pro coach', function () {
    Mail::fake();
    $coach = makeProCoach();

    $response = $this->actingAs($coach)->post(route('client.customers.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@gmail.com',
        'is_active' => true,
        'send_invitation' => true,
    ]);

    $response->assertRedirect(route('client.customers.index'));
    $customer = Customer::where('email', 'john@gmail.com')->firstOrFail();
    $this->assertDatabaseHas('customer_invitations', [
        'customer_id' => $customer->id,
        'email' => 'john@gmail.com',
    ]);
    Mail::assertSent(CustomerInvitationMail::class);
});
