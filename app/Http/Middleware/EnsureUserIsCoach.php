<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts coach-only routes. Client accounts are redirected to their own
 * space; coaches and admins pass through. (Guests are stopped upstream by `auth`.)
 */
class EnsureUserIsCoach
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isClientAccount()) {
            return redirect()
                ->route('client.space.index')
                ->with('error', "Cet espace est réservé aux coachs.");
        }

        return $next($request);
    }
}
