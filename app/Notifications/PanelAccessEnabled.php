<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PanelAccessEnabled extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct()
    {
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
                'heading' => 'Tu acceso al panel está habilitado',
                'preheader' => 'Ya podés ingresar a tu cuenta en Divas Cuyo.',
            ])
            ->subject('Acceso al panel habilitado · Divas Cuyo')
            ->greeting(filled($notifiable->name) ? 'Hola '.$notifiable->name : 'Hola')
            ->line('Revisamos tu inscripción y habilitamos tu acceso al panel de Divas Cuyo.')
            ->line('Ya podés ingresar para completar tu perfil, presentar tu documentación de identidad y cargar tu contenido.')
            ->action('Ingresar a mi cuenta', route('account.dashboard'))
            ->line('La habilitación del panel no publica tu perfil automáticamente. Desde tu cuenta podés consultar los pasos pendientes para publicarlo.');
    }
}
