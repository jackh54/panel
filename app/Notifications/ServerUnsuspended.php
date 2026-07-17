<?php

namespace Pterodactyl\Notifications;

use Pterodactyl\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ServerUnsuspended extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $afterCommit = true;

    public function __construct(public Server $server)
    {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage())
            ->subject('Server Unsuspended: ' . $this->server->name)
            ->greeting('Hello ' . $this->server->user->name . '!')
            ->line('Your server has been unsuspended and is available again.')
            ->line('Server Name: ' . $this->server->name)
            ->action('Visit Server', url('/server/' . $this->server->uuidShort));
    }
}
