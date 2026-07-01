<?php

namespace App\Http\Controllers;

use App\Mail\CustomerInvitationMail;
use App\Models\Customer;
use App\Models\CustomerInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CustomerInvitationsController extends Controller
{
    /**
     * Envoie une invitation à un client pour qu'il crée son compte et soit
     * associé à sa fiche. Réservée aux coachs abonnés (même règle que la
     * création de clients).
     */
    public function store(Customer $customer): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Même restriction Pro que la création de clients.
        if (! $user->can('create', Customer::class)) {
            return redirect()->route('client.customers.show', $customer)
                ->with('error', 'L\'invitation de clients est réservée aux abonnés Pro. Passez à Pro pour inviter vos clients.');
        }

        // Le coach ne peut inviter que ses propres clients.
        abort_unless($customer->user_id === $user->id, 403);

        if (! $customer->email) {
            return redirect()->route('client.customers.show', $customer)
                ->with('error', 'Ce client n\'a pas d\'adresse email. Ajoutez-en une avant d\'envoyer une invitation.');
        }

        if ($customer->isLinkedToAccount()) {
            return redirect()->route('client.customers.show', $customer)
                ->with('error', 'Ce client possède déjà un compte associé.');
        }

        $existingInvitation = CustomerInvitation::query()
            ->where('customer_id', $customer->id)
            ->whereNull('accepted_at')
            ->orderByDesc('created_at')
            ->first();

        if ($existingInvitation && ! $existingInvitation->isExpired()) {
            return redirect()->route('client.customers.show', $customer)
                ->with('error', 'Une invitation est déjà en attente pour ce client.');
        }

        if ($existingInvitation) {
            $existingInvitation->delete();
        }

        $invitation = CustomerInvitation::create([
            'customer_id' => $customer->id,
            'invited_by_user_id' => $user->id,
            'email' => $customer->email,
            'token' => Str::uuid()->toString(),
            'expires_at' => now()->addHours(48),
        ]);

        Mail::to($customer->email)->send(new CustomerInvitationMail($invitation));

        return redirect()->route('client.customers.show', $customer)
            ->with('success', 'Invitation envoyée à '.$customer->email.'.');
    }

    /**
     * Landing page publique d'acceptation.
     */
    public function show(string $token): Response|RedirectResponse
    {
        $invitation = CustomerInvitation::query()
            ->with(['customer', 'inviter'])
            ->where('token', $token)
            ->first();

        $status = 'valid';
        if (! $invitation) {
            $status = 'not_found';
        } elseif ($invitation->isUsed()) {
            $status = 'used';
        } elseif ($invitation->isExpired()) {
            $status = 'expired';
        }

        $user = Auth::user();
        $canAccept = false;
        if ($invitation && $status === 'valid' && $user) {
            if ($invitation->customer?->account_user_id !== null) {
                $status = 'used';
            } else {
                $canAccept = $user->email === $invitation->email;
            }
        }

        $email = $invitation?->email ?? '';

        return Inertia::render('customers/Invitation', [
            'status' => $status,
            'invitation' => $invitation ? [
                'coach_name' => $invitation->inviter?->name,
                'email' => $invitation->email,
                'expires_at' => optional($invitation->expires_at)->toDateTimeString(),
                'token' => $invitation->token,
            ] : null,
            'canAccept' => $canAccept,
            'isAuthenticated' => $user !== null,
            'acceptUrl' => $invitation ? route('customers.invitations.accept', $invitation->token) : null,
            'registerUrl' => $invitation
                ? url('/register?customer_invite='.$invitation->token.'&email='.urlencode($email).'&role=client')
                : null,
            'loginUrl' => $invitation
                ? url('/login?email='.urlencode($email))
                : url('/login'),
        ]);
    }

    /**
     * Acceptation par un utilisateur déjà authentifié : associe sa fiche client.
     */
    public function accept(string $token): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $invitation = CustomerInvitation::query()
            ->with('customer')
            ->where('token', $token)
            ->first();

        if (! $invitation || $invitation->isUsed() || $invitation->isExpired()) {
            return redirect()->route('customers.invitations.show', $token)
                ->with('error', 'Cette invitation n\'est plus valide.');
        }

        if ($invitation->email !== $user->email) {
            return redirect()->route('customers.invitations.show', $token)
                ->with('error', 'Cette invitation ne correspond pas à votre adresse email.');
        }

        $customer = $invitation->customer;

        if ($customer && $customer->account_user_id === null) {
            $customer->update(['account_user_id' => $user->id]);
        }

        $invitation->update([
            'accepted_at' => now(),
            'invited_user_id' => $user->id,
        ]);

        return redirect()->route('client.space.index')
            ->with('success', 'Votre compte est désormais lié à votre coach.');
    }
}
