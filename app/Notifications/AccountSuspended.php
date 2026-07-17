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

    public bool $afterCommit = true;

    public function __construct(public User $user)
    {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage())
            ->error()
            ->subject('Account Suspended')
            ->greeting('Hello ' . $this->user->name . '!')
            ->line('Your account has been suspended. You will not be able to sign in until an administrator unsuspends your account.')
            ->line('Any servers you own have also been suspended.');
    }
}
