<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Campus Reserve password was changed')
            ->greeting('Password changed')
            ->line('Your Campus Reserve password was changed successfully.')
            ->line('If you made this change, you can safely ignore this email.')
            ->line('If you did not change your password, your account may be compromised. Secure it immediately.')
            ->action('Secure my account', route('security.compromised'))
            ->salutation('Campus Reserve Security');
    }
}
