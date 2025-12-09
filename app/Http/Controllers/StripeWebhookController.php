<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

class StripeWebhookController extends CashierController
{
    /**
     * Handle a Stripe webhook call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {
        // Vérifier que le secret webhook est configuré
        $webhookSecret = config('cashier.webhook.secret');
        if (empty($webhookSecret)) {
            Log::error('Stripe Webhook secret not configured');
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        // Log pour déboguer
        Log::info('Stripe Webhook received', [
            'method' => $request->method(),
            'path' => $request->path(),
            'has_signature' => $request->hasHeader('Stripe-Signature'),
        ]);

        try {
            return parent::handleWebhook($request);
        } catch (\Exception $e) {
            Log::error('Stripe Webhook error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            // Retourner 200 pour éviter que Stripe réessaie indéfiniment
            return response()->json(['error' => $e->getMessage()], 200);
        }
    }

    /**
     * Handle invoice payment failed.
     * 
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleInvoicePaymentFailed(array $payload)
    {
        $invoice = $payload['data']['object'];
        $stripeCustomerId = $invoice['customer'];
        
        // Récupérer l'utilisateur par son Stripe ID
        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if ($user) {
            Log::warning('Échec de paiement pour l\'utilisateur', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'invoice_id' => $invoice['id'],
                'amount' => $invoice['amount_due'] / 100,
            ]);
        }

        return parent::handleInvoicePaymentFailed($payload);
    }

    /**
     * Handle customer subscription deleted.
     * 
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        $subscription = $payload['data']['object'];
        $stripeCustomerId = $subscription['customer'];
        
        // Récupérer l'utilisateur par son Stripe ID
        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if ($user) {
            Log::info('Abonnement supprimé pour l\'utilisateur', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'subscription_id' => $subscription['id'],
            ]);
        }

        return parent::handleCustomerSubscriptionDeleted($payload);
    }
}

