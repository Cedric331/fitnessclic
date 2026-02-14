<?php

namespace App\Mail;

use App\Models\Popin;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PopinPromoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Popin $popin,
        public string $registerUrl
    ) {}

    /**
     * Build the message.
     */
    public function build(): self
    {
        $subject = $this->popin->title ?: 'Votre offre FitnessClic';

        return $this->subject($subject)
            ->view('emails.popin-promo')
            ->with([
                'title' => $this->popin->title,
                'content' => $this->popin->content,
                'promoCode' => $this->popin->promo_code,
                'registerUrl' => $this->registerUrl,
            ]);
    }
}

