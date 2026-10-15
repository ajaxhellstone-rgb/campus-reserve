<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetLink extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $resetUrl) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your Campus Reserve password')
            ->greeting('Password reset requested')
            ->line('Use the button below to reset your Campus Reserve password. This link expires in 60 minutes.')
            ->action('Reset password', $this->resetUrl)
            ->line('If you did not request a reset, you can ignore this email.');
    }
}
