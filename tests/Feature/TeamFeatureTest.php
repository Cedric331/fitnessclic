<?php

use App\Mail\TeamDeletedMail;
use App\Mail\TeamInvitationMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

beforeEach(function () {
    Mail::fake();
});

function createTeamForUser(User $owner, string $name = 'Equipe Test'): Team
{
    $team = Team::create([
        'name' => $name,
        'owner_id' => $owner->id,
    ]);

    $team->members()->attach($owner->id);

    return $team;
}

test('user can create a team', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('team.store'), [
        'name' => 'Ma Team',
    ]);

    $response->assertRedirect(route('team.index'));
    $this->assertDatabaseHas('team_user', [
        'team_id' => Team::where('name', 'Ma Team')->value('id'),
        'user_id' => $user->id,
    ]);
    $this->assertDatabaseHas('teams', [
        'name' => 'Ma Team',
        'owner_id' => $user->id,
    ]);
});

test('user can create multiple teams', function () {
    $user = User::factory()->create();
    createTeamForUser($user);

    $response = $this->actingAs($user)->post(route('team.store'), [
        'name' => 'Autre Team',
    ]);

    $response->assertRedirect(route('team.index'));
    expect(Team::where('owner_id', $user->id)->count())->toBe(2);
});

test('team index lists all teams for a member', function () {
    $user = User::factory()->create();
    $teamA = createTeamForUser($user, 'Equipe A');
    $ownerB = User::factory()->create();
    $teamB = createTeamForUser($ownerB, 'Equipe B');
    $teamB->members()->attach($user->id);

    $response = $this->actingAs($user)->get(route('team.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('team/Index')
        ->has('teams', 2)
        ->where('teams', function ($teams) use ($teamA, $teamB) {
            $ids = collect($teams)->pluck('id')->sort()->values()->all();
            return $ids === collect([$teamA->id, $teamB->id])->sort()->values()->all();
        })
    );
});

test('team invitations list excludes teams user already joined', function () {
    $user = User::factory()->create(['email' => 'member@example.com']);
    $team = createTeamForUser($user, 'Equipe A');

    TeamInvitation::create([
        'team_id' => $team->id,
        'email' => $user->email,
        'token' => (string) Str::uuid(),
        'invited_by_user_id' => $user->id,
        'expires_at' => now()->addDay(),
    ]);

    $response = $this->actingAs($user)->get(route('team.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('team/Index')
        ->has('userInvitations', 0)
    );
});

test('owner can invite a coach by email', function () {
    $owner = User::factory()->create();
    $team = createTeamForUser($owner);

    $response = $this->actingAs($owner)->post(route('team.invitations.store'), [
        'email' => 'invitee@example.com',
        'team_id' => $team->id,
    ]);

    $response->assertRedirect(route('team.index'));
    $this->assertDatabaseHas('team_invitations', [
        'team_id' => $team->id,
        'email' => 'invitee@example.com',
    ]);
    Mail::assertSent(TeamInvitationMail::class);
});

test('owner cannot send duplicate pending invitation', function () {
    $owner = User::factory()->create();
    $team = createTeamForUser($owner);

    TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'invitee@example.com',
        'token' => (string) Str::uuid(),
        'invited_by_user_id' => $owner->id,
        'expires_at' => now()->addDay(),
    ]);

    $response = $this->actingAs($owner)->post(route('team.invitations.store'), [
        'email' => 'invitee@example.com',
        'team_id' => $team->id,
    ]);

    $response->assertRedirect(route('team.index'));
    $response->assertSessionHas('error', 'Une invitation est déjà en attente pour cet email.');
    expect(TeamInvitation::where('team_id', $team->id)->count())->toBe(1);
});

test('existing user can accept invitation from team page', function () {
    $owner = User::factory()->create();
    $team = createTeamForUser($owner);
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'invitee@example.com',
        'token' => (string) Str::uuid(),
        'invited_by_user_id' => $owner->id,
        'expires_at' => now()->addDay(),
    ]);

    $response = $this->actingAs($invitee)->post(route('team.invitations.accept-user', $invitation));

    $response->assertRedirect(route('team.index'));
    $invitee->refresh();
    expect($invitee->teams()->where('teams.id', $team->id)->exists())->toBeTrue();
    $invitation->refresh();
    expect($invitation->accepted_at)->not->toBeNull();
});

test('existing user can decline invitation from team page', function () {
    $owner = User::factory()->create();
    $team = createTeamForUser($owner);
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'invitee@example.com',
        'token' => (string) Str::uuid(),
        'invited_by_user_id' => $owner->id,
        'expires_at' => now()->addDay(),
    ]);

    $response = $this->actingAs($invitee)->post(route('team.invitations.decline-user', $invitation));

    $response->assertRedirect(route('team.index'));
    $this->assertDatabaseMissing('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('member can leave a team', function () {
    $owner = User::factory()->create();
    $team = createTeamForUser($owner);
    $member = User::factory()->create();
    $team->members()->attach($member->id);

    $response = $this->actingAs($member)->post(route('team.leave', $team));

    $response->assertRedirect(route('team.index'));
    expect($team->members()->where('users.id', $member->id)->exists())->toBeFalse();
});

test('owner cannot leave team without transfer', function () {
    $owner = User::factory()->create();
    $team = createTeamForUser($owner);

    $response = $this->actingAs($owner)->post(route('team.leave', $team));

    $response->assertRedirect(route('team.index'));
    $response->assertSessionHas('error');
});

test('owner can transfer ownership to a member', function () {
    $owner = User::factory()->create();
    $team = createTeamForUser($owner);
    $member = User::factory()->create();
    $team->members()->attach($member->id);

    $response = $this->actingAs($owner)->post(route('team.transfer-ownership', [$team, $member]));

    $response->assertRedirect(route('team.index'));
    $team->refresh();
    expect($team->owner_id)->toBe($member->id);
});

test('owner can delete team and members are notified', function () {
    $owner = User::factory()->create();
    $team = createTeamForUser($owner, 'Team Delete');
    $member = User::factory()->create();
    $team->members()->attach($member->id);

    $response = $this->actingAs($owner)->delete(route('team.destroy', $team));

    $response->assertRedirect(route('team.index'));
    $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    $this->assertDatabaseMissing('team_user', [
        'team_id' => $team->id,
        'user_id' => $member->id,
    ]);
    Mail::assertSent(TeamDeletedMail::class);
});

test('authenticated users are redirected when accessing invitation link', function () {
    $user = User::factory()->create();
    $token = (string) Str::uuid();
    TeamInvitation::create([
        'team_id' => createTeamForUser($user)->id,
        'email' => $user->email,
        'token' => $token,
        'invited_by_user_id' => $user->id,
        'expires_at' => now()->addDay(),
    ]);

    $response = $this->actingAs($user)->get(route('team.invitations.show', $token));

    $response->assertRedirect(route('dashboard'));
});

