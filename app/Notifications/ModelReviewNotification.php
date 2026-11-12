<?php

namespace App\Notifications;

use App\Enums\ModelNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ModelReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly ModelNotificationType $type,
        public readonly ?string $detail = null,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->view(['html' => 'emails.account', 'text' => 'emails.account-text'], [
                'heading' => $this->type->label(),
                'preheader' => 'Hay novedades sobre la revisión de tu cuenta en Divas Cuyo.',
                'footer' => 'Este mensaje está relacionado con la revisión de tu perfil y contenido.',
            ])
            ->subject($this->type->label().' · Divas Cuyo')
            ->greeting(filled($notifiable->name) ? 'Hola '.$notifiable->name : 'Hola')
            ->line($this->type->message($this->detail))
            ->action('Ver mi cuenta', route('account.dashboard'))
            ->line('Ingresá a tu cuenta para consultar el estado de tu inscripción, identidad y contenido.');
    }
}
