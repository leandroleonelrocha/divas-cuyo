<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyModelEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->view(['html' => 'emails.account', 'text' => 'emails.account-text'], [
                'heading' => 'Confirmá tu correo',
                'preheader' => 'Activá tu cuenta en Divas Cuyo. El enlace vence en 24 horas.',
            ])
            ->subject('Verificá tu correo electrónico')
            ->greeting('Hola '.$notifiable->name)
            ->line('Para activar tu cuenta, verificá tu correo electrónico con el siguiente enlace:')
            ->action('Verificar correo', route('verification.verify', ['token' => $this->token]))
            ->line('El enlace vence en 24 horas.')
            ->line('Si no creaste esta cuenta, podés ignorar este mensaje.');
    }
}
