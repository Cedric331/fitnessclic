<?php

use App\Models\CoachProfile;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(fn () => $this->withoutVite());

function publishedCoach(string $slug = 'coach-test'): User
{
    $coach = User::factory()->coach()->create();
    CoachProfile::create(['user_id' => $coach->id, 'slug' => $slug, 'city' => 'Paris', 'is_published' => true]);

    return $coach;
}

it('lets a client start a conversation with a coach and reuses it', function () {
    $coach = publishedCoach();
    $client = User::factory()->client()->create();

    actingAs($client)
        ->post('/messages/start', ['coach_slug' => 'coach-test'])
        ->assertRedirect();

    expect(Conversation::count())->toBe(1);

    // Starting again returns the same conversation (no duplicate).
    actingAs($client)->post('/messages/start', ['coach_slug' => 'coach-test']);
    expect(Conversation::count())->toBe(1);
});

it('forbids a coach from starting a conversation', function () {
    publishedCoach();
    $coach = User::factory()->coach()->create();

    actingAs($coach)->post('/messages/start', ['coach_slug' => 'coach-test'])->assertForbidden();
});

it('posts a reply and notifies the recipient once until read', function () {
    Notification::fake();

    $coach = publishedCoach();
    $client = User::factory()->client()->create();
    $conversation = Conversation::create(['coach_id' => $coach->id, 'client_id' => $client->id]);

    // Client sends two messages without the coach reading in between → one email.
    actingAs($client)->post("/messages/{$conversation->id}/reply", ['body' => 'Bonjour']);
    actingAs($client)->post("/messages/{$conversation->id}/reply", ['body' => 'Vous êtes là ?']);

    expect($conversation->messages()->count())->toBe(2);
    Notification::assertSentToTimes($coach, NewMessageNotification::class, 1);
    Notification::assertNothingSentTo($client);
});

it('marks incoming messages as read when the thread is opened', function () {
    $coach = publishedCoach();
    $client = User::factory()->client()->create();
    $conversation = Conversation::create(['coach_id' => $coach->id, 'client_id' => $client->id]);
    $conversation->messages()->create(['sender_id' => $client->id, 'body' => 'Coucou']);

    expect($coach->unreadMessagesCount())->toBe(1);

    actingAs($coach)
        ->get("/messages/{$conversation->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('messaging/Show')->has('messages', 1));

    expect($coach->fresh()->unreadMessagesCount())->toBe(0);
});

it('forbids a non-participant from viewing a conversation', function () {
    $coach = publishedCoach();
    $client = User::factory()->client()->create();
    $conversation = Conversation::create(['coach_id' => $coach->id, 'client_id' => $client->id]);

    $intruder = User::factory()->coach()->create();
    actingAs($intruder)->get("/messages/{$conversation->id}")->assertForbidden();
});

it('returns the unread counter', function () {
    $coach = publishedCoach();
    $client = User::factory()->client()->create();
    $conversation = Conversation::create(['coach_id' => $coach->id, 'client_id' => $client->id]);
    $conversation->messages()->create(['sender_id' => $client->id, 'body' => 'Hello']);

    actingAs($coach)->get('/messages/unread-count')->assertOk()->assertJson(['count' => 1]);
});

it('exposes a read receipt once the other participant has read the message', function () {
    $coach = publishedCoach();
    $client = User::factory()->client()->create();
    $conversation = Conversation::create(['coach_id' => $coach->id, 'client_id' => $client->id]);
    $conversation->messages()->create(['sender_id' => $client->id, 'body' => 'Salut']);

    // Before the coach reads it: not seen yet from the client's view.
    actingAs($client)->get("/messages/{$conversation->id}")
        ->assertInertia(fn ($page) => $page->where('messages.0.read', false));

    // The coach opens the thread → the message is marked read.
    actingAs($coach)->get("/messages/{$conversation->id}");

    // Now the client sees the read receipt on their sent message.
    actingAs($client)->get("/messages/{$conversation->id}")
        ->assertInertia(fn ($page) => $page
            ->where('messages.0.mine', true)
            ->where('messages.0.read', true));
});

it('lets a coach start a conversation from a linked customer fiche', function () {
    $coach = User::factory()->coach()->create();
    $client = User::factory()->client()->create(['email' => 'linked@x.fr']);
    $customer = Customer::create([
        'user_id' => $coach->id,
        'account_user_id' => $client->id,
        'first_name' => 'L', 'last_name' => 'X', 'email' => 'linked@x.fr',
    ]);

    actingAs($coach)
        ->post("/customers/{$customer->id}/message")
        ->assertRedirect();

    $conversation = Conversation::first();
    expect($conversation->coach_id)->toBe($coach->id)
        ->and($conversation->client_id)->toBe($client->id);
});

it('lets an admin owner message a linked customer too', function () {
    $admin = User::factory()->admin()->create();
    $client = User::factory()->client()->create(['email' => 'adminlink@x.fr']);
    $customer = Customer::create([
        'user_id' => $admin->id,
        'account_user_id' => $client->id,
        'first_name' => 'A', 'last_name' => 'L', 'email' => 'adminlink@x.fr',
    ]);

    actingAs($admin)->post("/customers/{$customer->id}/message")->assertRedirect();
    expect(Conversation::where('coach_id', $admin->id)->where('client_id', $client->id)->exists())->toBeTrue();
});

it('rejects messaging a customer without a linked account', function () {
    $coach = User::factory()->coach()->create();
    $customer = Customer::create([
        'user_id' => $coach->id,
        'first_name' => 'No', 'last_name' => 'Account', 'email' => 'noaccount@x.fr',
    ]);

    actingAs($coach)->post("/customers/{$customer->id}/message")->assertStatus(422);
    expect(Conversation::count())->toBe(0);
});

it('forbids messaging a customer belonging to another coach', function () {
    $owner = User::factory()->coach()->create();
    $client = User::factory()->client()->create(['email' => 'c@x.fr']);
    $customer = Customer::create([
        'user_id' => $owner->id,
        'account_user_id' => $client->id,
        'first_name' => 'C', 'last_name' => 'X', 'email' => 'c@x.fr',
    ]);

    $stranger = User::factory()->coach()->create();
    actingAs($stranger)->post("/customers/{$customer->id}/message")->assertForbidden();
});

it('redirects guests away from messaging', function () {
    get('/messages')->assertRedirect(route('login'));
});

it('exposes a client\'s coaches as contacts in the messaging index', function () {
    $coach = publishedCoach('coach-contacts');
    $coach->update(['name' => 'Coach Contact']);
    $client = User::factory()->client()->create(['email' => 'contact-client@x.fr']);
    Customer::create([
        'user_id' => $coach->id,
        'account_user_id' => $client->id,
        'first_name' => 'Co', 'last_name' => 'Cli', 'email' => 'contact-client@x.fr',
    ]);

    actingAs($client)
        ->get('/messages')
        ->assertInertia(fn ($page) => $page
            ->component('messaging/Index')
            ->has('contacts', 1)
            ->where('contacts.0.user_id', $coach->id));
});

it('exposes a coach\'s linked clients as contacts and ignores unlinked ones', function () {
    $coach = User::factory()->coach()->create();
    $client = User::factory()->client()->create(['email' => 'linked@x.fr']);
    Customer::create([
        'user_id' => $coach->id,
        'account_user_id' => $client->id,
        'first_name' => 'Lin', 'last_name' => 'Ked', 'email' => 'linked@x.fr',
    ]);
    // Sans compte lié → ne doit pas apparaître.
    Customer::create([
        'user_id' => $coach->id,
        'first_name' => 'No', 'last_name' => 'Account', 'email' => 'noacct@x.fr',
    ]);

    actingAs($coach)
        ->get('/messages')
        ->assertInertia(fn ($page) => $page
            ->has('contacts', 1)
            ->where('contacts.0.user_id', $client->id)
            ->where('contacts.0.name', 'Lin Ked'));
});

it('lets a client start a conversation with a coach from their contacts', function () {
    $coach = publishedCoach('coach-startwith');
    $client = User::factory()->client()->create(['email' => 'startwith@x.fr']);
    Customer::create([
        'user_id' => $coach->id,
        'account_user_id' => $client->id,
        'first_name' => 'S', 'last_name' => 'W', 'email' => 'startwith@x.fr',
    ]);

    actingAs($client)->post("/messages/with/{$coach->id}")->assertRedirect();
    expect(Conversation::where('coach_id', $coach->id)->where('client_id', $client->id)->exists())->toBeTrue();

    // Réutilise la même conversation.
    actingAs($client)->post("/messages/with/{$coach->id}");
    expect(Conversation::count())->toBe(1);
});

it('lets a coach start a conversation with a linked client from their contacts', function () {
    $coach = User::factory()->coach()->create();
    $client = User::factory()->client()->create(['email' => 'coachstart@x.fr']);
    Customer::create([
        'user_id' => $coach->id,
        'account_user_id' => $client->id,
        'first_name' => 'C', 'last_name' => 'S', 'email' => 'coachstart@x.fr',
    ]);

    actingAs($coach)->post("/messages/with/{$client->id}")->assertRedirect();
    expect(Conversation::where('coach_id', $coach->id)->where('client_id', $client->id)->exists())->toBeTrue();
});

it('forbids starting a conversation with an unpublished coach', function () {
    $client = User::factory()->client()->create();
    $strangerCoach = User::factory()->coach()->create(); // pas de profil publié

    actingAs($client)->post("/messages/with/{$strangerCoach->id}")->assertForbidden();
    expect(Conversation::count())->toBe(0);
});

it('lets a client start a conversation with any published coach (no prior relationship)', function () {
    $client = User::factory()->client()->create();
    $coach = publishedCoach('directory-coach');

    actingAs($client)->post("/messages/with/{$coach->id}")->assertRedirect();
    expect(Conversation::where('coach_id', $coach->id)->where('client_id', $client->id)->exists())->toBeTrue();
});

it('searches the published coach directory for a client', function () {
    $client = User::factory()->client()->create();
    $alice = publishedCoach('alice-coach');
    $alice->update(['name' => 'Alice Dupont']);
    $bob = publishedCoach('bob-coach');
    $bob->update(['name' => 'Bob Martin']);
    $unpublished = User::factory()->coach()->create(['name' => 'Alice Cachée']);

    actingAs($client)
        ->getJson('/messages/coaches/search?q=Alice')
        ->assertOk()
        ->assertJsonCount(1, 'coaches')
        ->assertJsonPath('coaches.0.user_id', $alice->id);
});

it('forbids a coach from using the client coach-search endpoint', function () {
    $coach = User::factory()->coach()->create();

    actingAs($coach)->getJson('/messages/coaches/search?q=x')->assertForbidden();
});

it('lets an admin browse conversations and open one in the admin panel', function () {
    $admin = User::factory()->admin()->create();
    $coach = User::factory()->coach()->create();
    $client = User::factory()->client()->create(['email' => 'adminbrowse@x.fr']);
    $conversation = Conversation::create(['coach_id' => $coach->id, 'client_id' => $client->id]);
    $conversation->messages()->create(['sender_id' => $coach->id, 'body' => 'Bonjour']);

    actingAs($admin)->get('/admin/conversations')->assertOk();
    actingAs($admin)->get("/admin/conversations/{$conversation->id}")->assertOk();
});

it('forbids a non-admin from the admin conversations panel', function () {
    $coach = User::factory()->coach()->create();

    actingAs($coach)->get('/admin/conversations')->assertForbidden();
});

it('throttles message sending after the per-minute limit', function () {
    $coach = User::factory()->coach()->create();
    $client = User::factory()->client()->create(['email' => 'throttle@x.fr']);
    $conversation = Conversation::create(['coach_id' => $coach->id, 'client_id' => $client->id]);

    actingAs($client);

    // 30 envois autorisés dans la minute.
    for ($i = 0; $i < 30; $i++) {
        $this->post("/messages/{$conversation->id}/reply", ['body' => "msg {$i}"])
            ->assertRedirect();
    }

    // Le 31e est bloqué.
    $this->post("/messages/{$conversation->id}/reply", ['body' => 'de trop'])
        ->assertTooManyRequests();
});
