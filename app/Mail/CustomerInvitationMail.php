<?php

namespace App\Mail;

use App\Models\CustomerInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public CustomerInvitation $invitation;

    /**
     * Create a new message instance.
     */
    public function __construct(CustomerInvitation $invitation)
    {
        $this->invitation = $invitation->loadMissing(['customer', 'inviter']);
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $coachName = $this->invitation->inviter?->name ?? 'Votre coach';
        $clientName = $this->invitation->customer?->first_name ?? null;
        $acceptUrl = route('customers.invitations.show', $this->invitation->token);

        return $this->subject("{$coachName} vous invite sur FitnessClic")
            ->view('emails.customer-invitation')
            ->with([
                'coachName' => $coachName,
                'clientName' => $clientName,
                'acceptUrl' => $acceptUrl,
                'expiresAt' => $this->invitation->expires_at,
                'logoUrl' => asset('assets/logo_fitnessclic.png'),
            ]);
    }
}
