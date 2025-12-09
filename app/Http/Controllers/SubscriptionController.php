<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Cashier;

class SubscriptionController extends Controller
{
    /**
     * Display the subscription page
     */
    public function index(): Response
    {
        $user = Auth::user();
        
        $subscription = $user->subscription('default');
        $hasActiveSubscription = $user->hasActiveSubscription();
        $onTrial = $subscription && $subscription->onTrial();
        $trialEndsAt = $onTrial ? $subscription->trial_ends_at : null;
        $daysLeftInTrial = $trialEndsAt ? now()->diffInDays($trialEndsAt, false) : 0;

        // Vérifier si l'abonnement est en cours d'annulation
        // Un abonnement est en cours d'annulation si :
        // - Il a une date ends_at dans le futur
        // - Il n'est pas déjà annulé (stripe_status != 'canceled')
        $isCancelling = false;
        $cancelsAt = null;
        $daysUntilCancellation = 0;
        
        if ($subscription) {
            $endsAt = $subscription->ends_at;
            $isCancelling = $endsAt && $endsAt->isFuture() && $subscription->stripe_status !== 'canceled';
            if ($isCancelling) {
                $cancelsAt = $endsAt;
                // Calculer le nombre de jours calendaires complets restants
                // Utiliser startOfDay() pour ignorer les heures et obtenir un nombre entier
                $daysUntilCancellation = max(0, (int) now()->startOfDay()->diffInDays($endsAt->startOfDay(), false));
            }
        }

        return Inertia::render('subscription/Index', [
            'hasActiveSubscription' => $hasActiveSubscription,
            'onTrial' => $onTrial,
            'trialEndsAt' => $trialEndsAt?->toDateTimeString(),
            'daysLeftInTrial' => max(0, $daysLeftInTrial),
            'subscriptionStatus' => $subscription?->stripe_status,
            'isCancelling' => $isCancelling,
            'cancelsAt' => $cancelsAt?->toDateTimeString(),
            'daysUntilCancellation' => max(0, $daysUntilCancellation),
        ]);
    }

    /**
     * Redirect to Stripe Checkout to subscribe
     */
    public function checkout(Request $request)
    {
        $user = Auth::user();

        // Récupérer le price ID depuis la configuration
        $priceId = config('cashier.price_id');

        if (!$priceId) {
            return redirect()->route('subscription.index')
                ->with('error', 'Configuration Stripe manquante. Veuillez contacter le support.');
        }

        try {
            $checkout = $user->checkout([$priceId], [
                'success_url' => route('subscription.success'),
                'cancel_url' => route('subscription.index'),
                'mode' => 'subscription',
            ]);

            // Si c'est une requête Inertia, retourner une réponse qui force une navigation complète
            if ($request->header('X-Inertia')) {
                // Retourner une réponse avec un header spécial pour forcer une navigation complète
                return response()->view('subscription.redirect', [
                    'url' => $checkout->url,
                ])->header('X-Inertia-Location', $checkout->url);
            }

            // Sinon, retourner une page HTML qui force une redirection JavaScript
            return response()->view('subscription.redirect', [
                'url' => $checkout->url,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('subscription.index')
                ->with('error', 'Une erreur est survenue lors de la création de la session de paiement.');
        }
    }

    /**
     * Redirect to Stripe Customer Portal for billing management
     */
    public function portal(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasStripeId()) {
            if ($request->header('X-Requested-With') === 'XMLHttpRequest' || $request->wantsJson()) {
                return response()->json([
                    'error' => 'Vous devez d\'abord créer un compte Stripe.',
                ], 400);
            }
            return redirect()->route('subscription.index')
                ->with('error', 'Vous devez d\'abord créer un compte Stripe.');
        }

        try {
            $portal = $user->billingPortalUrl(route('subscription.index'));
            
            // Si c'est une requête Inertia, retourner une réponse qui force une navigation complète
            if ($request->header('X-Inertia')) {
                return response()->view('subscription.redirect', [
                    'url' => $portal,
                ])->header('X-Inertia-Location', $portal);
            }

            // Sinon, retourner une page HTML qui force une redirection JavaScript
            return response()->view('subscription.redirect', [
                'url' => $portal,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('subscription.index')
                ->with('error', 'Une erreur est survenue lors de l\'accès au portail de facturation.');
        }
    }

    /**
     * Success page after checkout
     */
    public function success(): RedirectResponse
    {
        return redirect()->route('subscription.index')
            ->with('success', 'Votre abonnement a été activé avec succès !');
    }
}

