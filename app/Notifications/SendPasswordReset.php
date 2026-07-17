<?php

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SendPasswordReset extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesNotificationTemplate;

    public function __construct(public string $token)
    {
    }

    public function via(): array
    {
        return $this->notificationChannels('password_reset');
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return $this->templateMail('password_reset', [
            'email' => $notifiable->email,
            'action_url' => url('/auth/password/reset/' . $this->token . '?email=' . urlencode($notifiable->email)),
        ]);
    }
}
