<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
}

