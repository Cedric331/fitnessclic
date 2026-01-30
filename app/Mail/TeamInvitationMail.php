<?php

namespace App\Mail;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public TeamInvitation $invitation;

    /**
     * Create a new message instance.
     */
    public function __construct(TeamInvitation $invitation)
    {
        $this->invitation = $invitation->loadMissing(['team', 'inviter']);
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $teamName = $this->invitation->team?->name ?? 'Votre équipe';
        $inviterName = $this->invitation->inviter?->name ?? 'Un coach';
        $acceptUrl = route('team.invitations.show', $this->invitation->token);

        return $this->subject("Invitation à rejoindre l'équipe {$teamName}")
            ->view('emails.team-invitation')
            ->with([
                'teamName' => $teamName,
                'inviterName' => $inviterName,
                'acceptUrl' => $acceptUrl,
                'expiresAt' => $this->invitation->expires_at,
            ]);
    }
}

