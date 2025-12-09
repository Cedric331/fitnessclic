<?php

namespace App\Http\Controllers;

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
}

