<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->user->isClientAccount()
                ? 'Bienvenue sur FitnessClic : trouvez votre coach !'
                : 'Bienvenue sur FitnessClic !',
        );
    }

    /**
     * Get the message content definition.
     *
     * Le contenu est adapté au rôle : un client est orienté vers la recherche
     * d'un coach, un coach vers la gestion de ses séances et de son profil.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: array_merge([
                'userName' => $this->user->name,
                'logoUrl' => asset('assets/logo_fitnessclic.png'),
            ], $this->user->isClientAccount()
                ? $this->clientContent()
                : $this->coachContent()),
        );
    }

    /**
     * Variables de contenu propres à un client.
     *
     * @return array<string, mixed>
     */
    private function clientContent(): array
    {
        return [
            'headerSubtitle' => 'Trouvez le coach qui vous correspond',
            'paragraphs' => [
                'Merci pour votre inscription sur <strong>FitnessClic</strong> ! Nous sommes ravis de vous compter parmi nous.',
                'Parcourez notre annuaire de coachs sportifs, découvrez leurs spécialités et contactez celui ou celle qui correspond le mieux à vos objectifs.',
                "Une question ou besoin d'aide pour démarrer ? Notre équipe est là pour vous accompagner.",
            ],
            'ctaUrl' => route('coachs.index'),
            'ctaLabel' => 'Trouver mon coach',
        ];
    }

    /**
     * Variables de contenu propres à un coach.
     *
     * @return array<string, mixed>
     */
    private function coachContent(): array
    {
        return [
            'headerSubtitle' => 'Votre aventure commence maintenant',
            'paragraphs' => [
                'Merci pour votre inscription sur <strong>FitnessClic</strong> ! Nous sommes ravis de vous accueillir dans notre communauté de coachs sportifs passionnés.',
                "Vous disposez maintenant d'un outil puissant pour créer, gérer et partager vos séances d'entraînement personnalisées.",
                "Si vous avez des questions ou besoin d'aide, notre équipe est là pour vous accompagner. N'hésitez pas à nous contacter !",
            ],
            'ctaUrl' => route('login'),
            'ctaLabel' => 'Accéder à mon compte',
        ];
    }
}

