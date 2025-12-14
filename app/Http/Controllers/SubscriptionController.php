<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

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

        $isCancelling = false;
        $cancelsAt = null;
        $daysUntilCancellation = 0;

        if ($subscription) {
            $endsAt = $subscription->ends_at;
            $isCancelling = $endsAt && $endsAt->isFuture() && $subscription->stripe_status !== 'canceled';
            if ($isCancelling) {
                $cancelsAt = $endsAt;
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

        $priceId = config('cashier.price_id');

        if (! $priceId) {
            return redirect()->route('subscription.index')
                ->with('error', 'Configuration Stripe manquante. Veuillez contacter le support.');
        }

        try {
            $checkout = $user->checkout([$priceId], [
                'success_url' => route('subscription.success'),
                'cancel_url' => route('subscription.index'),
                'mode' => 'subscription',
            ]);

            if ($request->header('X-Inertia')) {
                return response()->view('subscription.redirect', [
                    'url' => $checkout->url,
                ])->header('X-Inertia-Location', $checkout->url);
            }

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

        if (! $user->hasStripeId()) {
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

            if ($request->header('X-Inertia')) {
                return response()->view('subscription.redirect', [
                    'url' => $portal,
                ])->header('X-Inertia-Location', $portal);
            }

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
