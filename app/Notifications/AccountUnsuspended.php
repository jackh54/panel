<?php

namespace Pterodactyl\Notifications;

use Pterodactyl\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AccountUnsuspended extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesNotificationTemplate;

    public function __construct(public User $user)
    {
    }

    public function via(): array
    {
        return $this->notificationChannels('account_unsuspended');
    }

    public function toMail(): MailMessage
    {
        return $this->templateMail('account_unsuspended', [
            'user_name' => $this->user->name,
            'action_url' => route('auth.login'),
        ]);
    }
}
