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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {
        $webhookSecret = config('cashier.webhook.secret');
        if (empty($webhookSecret)) {
            Log::error('Stripe Webhook secret not configured');

            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

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

            return response()->json(['error' => $e->getMessage()], 200);
        }
    }

    /**
     * Handle invoice payment failed.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleInvoicePaymentFailed(array $payload)
    {
        $invoice = $payload['data']['object'];
        $stripeCustomerId = $invoice['customer'];

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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        $subscription = $payload['data']['object'];
        $stripeCustomerId = $subscription['customer'];

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

    /**
     * Handle invoice payment succeeded.
     * This is called when a subscription payment is successful (initial or renewal).
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleInvoicePaymentSucceeded(array $payload)
    {
        $invoice = $payload['data']['object'];
        $stripeCustomerId = $invoice['customer'];

        // Only process subscription invoices
        if (empty($invoice['subscription'])) {
            return parent::handleInvoicePaymentSucceeded($payload);
        }

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if ($user) {
            // Check if this is the first invoice for this subscription (initial payment)
            // or a renewal (billing_reason: 'subscription_cycle')
            $billingReason = $invoice['billing_reason'] ?? null;

            // subscription_create = first payment, subscription_cycle = renewal
            if ($billingReason === 'subscription_create') {
                // First subscription - initialize credits
                $user->initializeAiCredits();

                Log::info('Crédits IA initialisés pour nouvel abonné', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'credits' => config('services.openai.credit_limit', 20),
                ]);
            } elseif ($billingReason === 'subscription_cycle') {
                // Subscription renewal - reset credits
                $user->resetAiCredits();

                Log::info('Crédits IA rechargés pour renouvellement', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'credits' => config('services.openai.credit_limit', 20),
                ]);
            }
        }

        // Important: appeler le parent pour que Cashier synchronise l'abonnement
        return parent::handleInvoicePaymentSucceeded($payload);
    }

    /**
     * Handle customer subscription created.
     * Fallback for initial credit assignment if invoice.payment_succeeded doesn't trigger.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        $subscription = $payload['data']['object'];
        $stripeCustomerId = $subscription['customer'];

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if ($user) {
            // Check if user already has credits (to avoid double-crediting)
            $currentBalance = $user->getAiCreditsBalance();

            if ($currentBalance === 0) {
                $user->initializeAiCredits();

                Log::info('Crédits IA initialisés lors de la création d\'abonnement', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'credits' => config('services.openai.credit_limit', 20),
                ]);
            }
        }

        // Important: appeler le parent pour que Cashier crée l'abonnement en base
        return parent::handleCustomerSubscriptionCreated($payload);
    }
}
