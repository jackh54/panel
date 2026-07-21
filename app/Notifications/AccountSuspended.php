<?php

namespace Pterodactyl\Notifications;

use Pterodactyl\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AccountSuspended extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesNotificationTemplate;

    public function __construct(public User $user)
    {
    }

    public function via(): array
    {
        return $this->notificationChannels('account_suspended');
    }

    public function toMail(): MailMessage
    {
        return $this->templateMail('account_suspended', [
            'user_name' => $this->user->name,
        ]);
    }
}
