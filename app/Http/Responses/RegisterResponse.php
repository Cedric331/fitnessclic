<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\ResolvesSafeRedirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

class RegisterResponse implements RegisterResponseContract
{
    use ResolvesSafeRedirect;

    /**
     * Redirige vers la destination demandée après inscription (ex. la fiche du
     * coach depuis laquelle un client s'est inscrit via « Contacter »). À défaut,
     * un coach est envoyé vers la gestion de son profil (via le middleware
     * `verified`, cette URL est mémorisée comme « intended » et le coach y est
     * ramené après vérification de son e-mail) ; sinon destination Fortify par
     * défaut. Mémorise aussi l'intention de contacter un coach pour ouvrir la
     * conversation après vérification e-mail.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        $this->rememberContactIntent($request);

        $redirect = (string) $request->input('redirect', '');

        if ($this->isSafeInternalPath($redirect)) {
            return redirect()->to($redirect);
        }

        $user = $request->user();

        if ($user && $user->isCoach()) {
            return redirect()->route('coach.profile.edit');
        }

        return redirect()->intended(Fortify::redirects('register'));
    }

    /**
     * Stocke (en cache, par utilisateur) le coach que le client souhaitait
     * contacter, pour ouvrir automatiquement la conversation une fois l'e-mail
     * vérifié. Clé par ID utilisateur → survit au clic du lien de vérification
     * même depuis un autre appareil.
     */
    private function rememberContactIntent($request): void
    {
        $user = $request->user();
        $slug = trim((string) $request->input('contact_coach', ''));

        if ($user && $slug !== '' && $user->isClientAccount()) {
            Cache::put("contact-intent:{$user->id}", $slug, now()->addDays(3));
        }
    }
}
