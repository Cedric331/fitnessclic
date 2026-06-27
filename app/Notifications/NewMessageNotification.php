<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public Message $message) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sender = $this->message->sender;
        $url = route('messages.show', $this->message->conversation_id);

        return (new MailMessage)
            ->subject('Nouveau message sur FitnessClic')
            ->greeting('Bonjour,')
            ->line("Vous avez reçu un nouveau message de {$sender?->name}.")
            ->action('Lire le message', $url)
            ->line('Connectez-vous à votre espace FitnessClic pour répondre.');
    }
}
