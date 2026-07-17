<?php

namespace Pterodactyl\Notifications;

use Pterodactyl\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MailTested extends Notification
{
    use UsesNotificationTemplate;

    public function __construct(private User $user)
    {
    }

    public function via(): array
    {
        return $this->notificationChannels('mail_tested');
    }

    public function toMail(): MailMessage
    {
        return $this->templateMail('mail_tested', [
            'user_name' => $this->user->name,
        ]);
    }
}
