<?php

namespace App\Http\Responses;

use App\Services\ConversationStarter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Fortify;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function __construct(private readonly ConversationStarter $starter)
    {
    }

    /**
     * Après vérification de l'e-mail, ouvre directement la conversation avec le
     * coach que le client souhaitait contacter (intention mémorisée à
     * l'inscription), sinon comportement Fortify par défaut.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $user = $request->user();
        $slug = $user ? Cache::pull("contact-intent:{$user->id}") : null;

        if ($user && $slug && $user->isClientAccount()) {
            $coach = $this->starter->coachBySlug($slug);

            if ($coach) {
                $conversation = $this->starter->startWithCoach($user, $coach);

                return redirect()->route('messages.show', $conversation->id);
            }
        }

        return redirect()->intended(Fortify::redirects('email-verification').'?verified=1');
    }
}
