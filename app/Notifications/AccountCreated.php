<?php

namespace Pterodactyl\Notifications;

use Pterodactyl\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AccountCreated extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesNotificationTemplate;

    public function __construct(public User $user, public ?string $token = null)
    {
    }

    public function via(): array
    {
        return $this->notificationChannels('account_created');
    }

    public function toMail(): MailMessage
    {
        $actionUrl = '';
        if (!is_null($this->token)) {
            $actionUrl = url('/auth/password/reset/' . $this->token . '?email=' . urlencode($this->user->email));
        }

        return $this->templateMail('account_created', [
            'user_name' => $this->user->name,
            'username' => $this->user->username,
            'email' => $this->user->email,
            'action_url' => $actionUrl,
        ]);
    }
}
