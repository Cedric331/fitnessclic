<?php

namespace App\Http\Controllers;

use App\Mail\PopinPromoMail;
use App\Models\Popin;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PopinsController extends Controller
{
    /**
     * Get the active popin for guests.
     */
    public function active(): JsonResponse
    {
        if (Auth::check()) {
            return response()->json(['popin' => null]);
        }

        $popin = Popin::active()->first();

        if (! $popin) {
            return response()->json(['popin' => null]);
        }

        return response()->json([
            'popin' => [
                'id' => $popin->id,
                'title' => $popin->title,
                'content' => $popin->content,
                'image_url' => $popin->image_url,
                'image_size' => $popin->image_size,
                'delay_seconds' => $popin->delay_seconds,
                'has_promo_code' => (bool) $popin->promo_code,
                'register_url' => url('/register'),
            ],
        ]);
    }

    /**
     * Store a prospect email for the popin.
     */
    public function storeProspect(Popin $popin, Request $request): JsonResponse
    {
        if (! $popin->is_active) {
            return response()->json(['message' => 'Cette offre n\'est plus disponible.'], 422);
        }

        $validated = $request->validate([
            'email' => ['required', 'email:rfc,dns', 'max:255'],
        ]);

        $email = strtolower($validated['email']);

        if (User::where('email', $email)->exists()) {
            return response()->json(['message' => 'Cet email est déjà associé à un compte.'], 422);
        }

        $prospect = Prospect::firstOrCreate([
            'popin_id' => $popin->id,
            'email' => $email,
        ]);

        if ($popin->promo_code && ! $prospect->promo_code_sent_at) {
            Mail::to($prospect->email)->send(new PopinPromoMail($popin, url('/register')));
            $prospect->forceFill(['promo_code_sent_at' => now()])->save();
        }

        return response()->json([
            'success' => true,
            'promo_code' => $popin->promo_code,
            'register_url' => url('/register'),
        ]);
    }
}

