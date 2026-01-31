<?php

namespace App\Http\Controllers;

use App\Mail\TeamInvitationMail;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeamInvitationsController extends Controller
{
    /**
     * Store a new team invitation.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $team = $user->team;

        if (! $team) {
            return redirect()->route('team.index')
                ->with('error', 'Créez une équipe avant d\'inviter un coach.');
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = $validated['email'];

        if ($email === $user->email) {
            return redirect()->route('team.index')
                ->with('error', 'Vous ne pouvez pas vous inviter vous-même.');
        }

        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $existingUser->team_id && $existingUser->team_id !== $team->id) {
            return redirect()->route('team.index')
                ->with('error', 'Ce coach appartient déjà à une autre équipe.');
        }

        if ($existingUser && $existingUser->team_id === $team->id) {
            return redirect()->route('team.index')
                ->with('error', 'Ce coach fait déjà partie de l\'équipe.');
        }

        $existingInvitation = TeamInvitation::query()
            ->where('team_id', $team->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->orderByDesc('created_at')
            ->first();

        if ($existingInvitation && ! $existingInvitation->isExpired()) {
            return redirect()->route('team.index')
                ->with('error', 'Une invitation est déjà en attente pour cet email.');
        }

        if ($existingInvitation) {
            $existingInvitation->delete();
        }

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => $email,
            'token' => Str::uuid()->toString(),
            'invited_by_user_id' => $user->id,
            'expires_at' => now()->addHours(48),
        ]);

        Mail::to($email)->send(new TeamInvitationMail($invitation));

        return redirect()->route('team.index')
            ->with('success', 'Invitation envoyée avec succès.');
    }

    /**
     * Display the invitation landing page.
     */
    public function show(string $token): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $invitation = TeamInvitation::query()
            ->with(['team', 'inviter'])
            ->where('token', $token)
            ->first();

        $status = 'valid';
        if (! $invitation) {
            $status = 'not_found';
        } elseif ($invitation->isUsed()) {
            $status = 'used';
        } elseif ($invitation->isExpired()) {
            $status = 'expired';
        }

        $user = Auth::user();
        $canAccept = false;
        if ($invitation && $status === 'valid' && $user) {
            $canAccept = $user->email === $invitation->email
                && (! $user->team_id || $user->team_id === $invitation->team_id);
        }

        return Inertia::render('team/Invitation', [
            'status' => $status,
            'invitation' => $invitation ? [
                'team_name' => $invitation->team?->name,
                'email' => $invitation->email,
                'inviter_name' => $invitation->inviter?->name,
                'expires_at' => optional($invitation->expires_at)->toDateTimeString(),
                'token' => $invitation->token,
            ] : null,
            'canAccept' => $canAccept,
            'isAuthenticated' => $user !== null,
            'acceptUrl' => $invitation ? route('team.invitations.accept', $invitation->token) : null,
            'registerUrl' => $invitation
                ? url('/register?invite='.$invitation->token.'&email='.urlencode($invitation->email))
                : null,
            'loginUrl' => $invitation
                ? url('/login?invite='.$invitation->token.'&email='.urlencode($invitation->email))
                : url('/login'),
        ]);
    }

    /**
     * Accept an invitation for an authenticated user.
     */
    public function accept(string $token): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $invitation = TeamInvitation::query()
            ->with('team')
            ->where('token', $token)
            ->first();

        if (! $invitation || $invitation->isUsed() || $invitation->isExpired()) {
            return redirect()->route('team.invitations.show', $token)
                ->with('error', 'Cette invitation n\'est plus valide.');
        }

        if ($invitation->email !== $user->email) {
            return redirect()->route('team.invitations.show', $token)
                ->with('error', 'Cette invitation ne correspond pas à votre adresse email.');
        }

        if ($user->team_id && $user->team_id !== $invitation->team_id) {
            return redirect()->route('team.invitations.show', $token)
                ->with('error', 'Vous faites déjà partie d\'une autre équipe.');
        }

        $user->update(['team_id' => $invitation->team_id]);

        $invitation->update([
            'accepted_at' => now(),
            'invited_user_id' => $user->id,
        ]);

        return redirect()->route('team.index')
            ->with('success', 'Bienvenue dans l\'équipe !');
    }

    /**
     * Accept an invitation from the team page for an existing user.
     */
    public function acceptForUser(TeamInvitation $invitation): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($invitation->isUsed() || $invitation->isExpired()) {
            return redirect()->route('team.index')
                ->with('error', 'Cette invitation n\'est plus valide.');
        }

        if ($invitation->email !== $user->email) {
            return redirect()->route('team.index')
                ->with('error', 'Cette invitation ne correspond pas à votre adresse email.');
        }

        if ($user->team_id) {
            return redirect()->route('team.index')
                ->with('error', 'Vous faites déjà partie d\'une équipe.');
        }

        $user->update(['team_id' => $invitation->team_id]);

        $invitation->update([
            'accepted_at' => now(),
            'invited_user_id' => $user->id,
        ]);

        return redirect()->route('team.index')
            ->with('success', 'Invitation acceptée. Bienvenue dans l\'équipe !');
    }

    /**
     * Decline an invitation from the team page.
     */
    public function declineForUser(TeamInvitation $invitation): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($invitation->email !== $user->email) {
            return redirect()->route('team.index')
                ->with('error', 'Cette invitation ne correspond pas à votre adresse email.');
        }

        if ($invitation->accepted_at) {
            return redirect()->route('team.index')
                ->with('error', 'Cette invitation a déjà été utilisée.');
        }

        $invitation->delete();

        return redirect()->route('team.index')
            ->with('success', 'Invitation refusée.');
    }

    /**
     * Cancel a pending invitation.
     */
    public function destroy(TeamInvitation $invitation): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $team = $user->team;

        if (! $team || $team->owner_id !== $user->id) {
            return redirect()->route('team.index')
                ->with('error', 'Vous n\'avez pas les permissions pour annuler cette invitation.');
        }

        if ($invitation->team_id !== $team->id) {
            return redirect()->route('team.index')
                ->with('error', 'Cette invitation ne fait pas partie de votre équipe.');
        }

        if ($invitation->accepted_at) {
            return redirect()->route('team.index')
                ->with('error', 'Cette invitation a déjà été utilisée.');
        }

        $invitation->delete();

        return redirect()->route('team.index')
            ->with('success', 'Invitation annulée.');
    }
}

