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

    $owner->update(['team_id' => $team->id]);

    return $team;
}

test('user can create a team', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('team.store'), [
        'name' => 'Ma Team',
    ]);

    $response->assertRedirect(route('team.index'));
    $user->refresh();
    expect($user->team_id)->not->toBeNull();
    $this->assertDatabaseHas('teams', [
        'name' => 'Ma Team',
        'owner_id' => $user->id,
    ]);
});

test('user cannot create a second team', function () {
    $user = User::factory()->create();
    createTeamForUser($user);

    $response = $this->actingAs($user)->post(route('team.store'), [
        'name' => 'Autre Team',
    ]);

    $response->assertRedirect(route('team.index'));
    $response->assertSessionHas('error', 'Vous avez déjà une équipe.');
});

test('owner can invite a coach by email', function () {
    $owner = User::factory()->create();
    $team = createTeamForUser($owner);

    $response = $this->actingAs($owner)->post(route('team.invitations.store'), [
        'email' => 'invitee@example.com',
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
    expect($invitee->team_id)->toBe($team->id);
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
    $member->update(['team_id' => $team->id]);

    $response = $this->actingAs($member)->post(route('team.leave'));

    $response->assertRedirect(route('team.index'));
    $member->refresh();
    expect($member->team_id)->toBeNull();
});

test('owner cannot leave team without transfer', function () {
    $owner = User::factory()->create();
    createTeamForUser($owner);

    $response = $this->actingAs($owner)->post(route('team.leave'));

    $response->assertRedirect(route('team.index'));
    $response->assertSessionHas('error');
});

test('owner can transfer ownership to a member', function () {
    $owner = User::factory()->create();
    $team = createTeamForUser($owner);
    $member = User::factory()->create();
    $member->update(['team_id' => $team->id]);

    $response = $this->actingAs($owner)->post(route('team.transfer-ownership', $member));

    $response->assertRedirect(route('team.index'));
    $team->refresh();
    expect($team->owner_id)->toBe($member->id);
});

test('owner can delete team and members are notified', function () {
    $owner = User::factory()->create();
    $team = createTeamForUser($owner, 'Team Delete');
    $member = User::factory()->create();
    $member->update(['team_id' => $team->id]);

    $response = $this->actingAs($owner)->delete(route('team.destroy'));

    $response->assertRedirect(route('team.index'));
    $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    $member->refresh();
    expect($member->team_id)->toBeNull();
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

