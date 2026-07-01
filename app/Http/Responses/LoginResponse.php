<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\ResolvesSafeRedirect;
use App\Models\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    use ResolvesSafeRedirect;

    /**
     * @return RedirectResponse
     */
    public function toResponse($request)
    {
        $user = $request->user();
        $token = $request->input('invite_token');

        if ($user && $token) {
            $invitation = TeamInvitation::query()
                ->with('team')
                ->where('token', $token)
                ->first();

            if (! $invitation) {
                return redirect()->route('team.invitations.show', $token)
                    ->with('error', 'Cette invitation est introuvable.');
            }

            if ($invitation->isUsed() || $invitation->isExpired()) {
                return redirect()->route('team.invitations.show', $token)
                    ->with('error', 'Cette invitation n\'est plus valide.');
            }

            if ($invitation->email !== $user->email) {
                return redirect()->route('team.invitations.show', $token)
                    ->with('error', 'Cette invitation ne correspond pas à votre adresse email.');
            }

            $user->teams()->syncWithoutDetaching([$invitation->team_id]);
            $invitation->update([
                'accepted_at' => now(),
                'invited_user_id' => $user->id,
            ]);

            return redirect()->route('team.index')
                ->with('success', 'Bienvenue dans l\'équipe !');
        }

        // Retour vers la page d'origine (ex. fiche du coach) si demandée.
        $redirect = (string) $request->input('redirect', '');
        if ($this->isSafeInternalPath($redirect)) {
            return redirect()->to($redirect);
        }

        return redirect()->intended(route('dashboard'));
    }
}

