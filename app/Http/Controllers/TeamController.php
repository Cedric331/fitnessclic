<?php

namespace App\Http\Controllers;

use App\Mail\TeamDeletedMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    /**
     * Display the team dashboard.
     */
    public function index(): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $team = $user->team?->load(['members', 'owner']);
        $pendingInvitations = $team
            ? TeamInvitation::query()
                ->with('inviter')
                ->where('team_id', $team->id)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->latest()
                ->get()
            : collect();

        $userInvitations = collect();
        if (! $team) {
            $userInvitations = TeamInvitation::query()
                ->with(['team', 'inviter'])
                ->where('email', $user->email)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->latest()
                ->get();
        }

        return Inertia::render('team/Index', [
            'team' => $team ? [
                'id' => $team->id,
                'name' => $team->name,
                'owner_id' => $team->owner_id,
            ] : null,
            'members' => $team
                ? $team->members->map(fn ($member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                ])
                : [],
            'pendingInvitations' => $pendingInvitations->map(fn ($invitation) => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'expires_at' => optional($invitation->expires_at)->toDateTimeString(),
                'invited_by' => $invitation->inviter?->name,
            ]),
            'userInvitations' => $userInvitations->map(fn ($invitation) => [
                'id' => $invitation->id,
                'team_name' => $invitation->team?->name,
                'expires_at' => optional($invitation->expires_at)->toDateTimeString(),
                'invited_by' => $invitation->inviter?->name,
            ]),
        ]);
    }

    /**
     * Create a new team for the user.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->team_id) {
            return redirect()->route('team.index')
                ->with('error', 'Vous avez déjà une équipe.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team = Team::create([
            'name' => $validated['name'],
            'owner_id' => $user->id,
        ]);

        $user->update(['team_id' => $team->id]);

        return redirect()->route('team.index')
            ->with('success', 'Équipe créée avec succès.');
    }

    /**
     * Remove a member from the team.
     */
    public function destroyMember(User $member): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $team = $user->team;

        if (! $team || $team->owner_id !== $user->id) {
            return redirect()->route('team.index')
                ->with('error', 'Vous n\'avez pas les permissions pour retirer ce coach.');
        }

        if ($member->team_id !== $team->id) {
            return redirect()->route('team.index')
                ->with('error', 'Ce coach ne fait pas partie de votre équipe.');
        }

        if ($member->id === $team->owner_id) {
            return redirect()->route('team.index')
                ->with('error', 'Vous ne pouvez pas retirer le propriétaire de l\'équipe.');
        }

        $member->update(['team_id' => null]);

        return redirect()->route('team.index')
            ->with('success', 'Coach retiré de l\'équipe.');
    }

    /**
     * Leave the current team.
     */
    public function leave(): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $team = $user->team;

        if (! $team) {
            return redirect()->route('team.index')
                ->with('error', 'Vous n\'êtes pas membre d\'une équipe.');
        }

        if ($team->owner_id === $user->id) {
            return redirect()->route('team.index')
                ->with('error', 'Vous devez transférer la propriété ou supprimer l\'équipe avant de partir.');
        }

        $user->update(['team_id' => null]);

        return redirect()->route('team.index')
            ->with('success', 'Vous avez quitté l\'équipe.');
    }

    /**
     * Transfer team ownership to another member.
     */
    public function transferOwnership(User $member): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $team = $user->team;

        if (! $team || $team->owner_id !== $user->id) {
            return redirect()->route('team.index')
                ->with('error', 'Vous n\'avez pas les permissions pour transférer la propriété.');
        }

        if ($member->team_id !== $team->id) {
            return redirect()->route('team.index')
                ->with('error', 'Ce coach ne fait pas partie de votre équipe.');
        }

        if ($member->id === $team->owner_id) {
            return redirect()->route('team.index')
                ->with('error', 'Ce coach est déjà propriétaire de l\'équipe.');
        }

        $team->update(['owner_id' => $member->id]);

        return redirect()->route('team.index')
            ->with('success', 'Propriétaire mis à jour.');
    }

    /**
     * Delete the team and notify members.
     */
    public function destroy(): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $team = $user->team?->load('members');

        if (! $team || $team->owner_id !== $user->id) {
            return redirect()->route('team.index')
                ->with('error', 'Vous n\'avez pas les permissions pour supprimer l\'équipe.');
        }

        $members = $team->members;
        $teamName = $team->name;

        foreach ($members as $member) {
            if ($member->email) {
                Mail::to($member->email)->send(new TeamDeletedMail($teamName));
            }
        }

        User::where('team_id', $team->id)->update(['team_id' => null]);
        $team->delete();

        return redirect()->route('team.index')
            ->with('success', 'Équipe supprimée. Les membres ont été informés.');
    }
}

