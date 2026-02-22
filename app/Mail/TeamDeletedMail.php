<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TeamDeletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $teamName;

    /**
     * Create a new message instance.
     */
    public function __construct(string $teamName)
    {
        $this->teamName = $teamName;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject('Votre équipe a été supprimée')
            ->view('emails.team-deleted')
            ->with([
                'teamName' => $this->teamName,
                'logoUrl' => asset('assets/logo_fitnessclic.png'),
            ]);
    }
}

