<?php

namespace Pterodactyl\Notifications;

use Pterodactyl\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ServerSuspended extends Notification implements ShouldQueue
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
            ->error()
            ->subject('Server Suspended: ' . $this->server->name)
            ->greeting('Hello ' . $this->server->user->name . '!')
            ->line('Your server has been suspended and is no longer accessible.')
            ->line('Server Name: ' . $this->server->name)
            ->action('Visit Panel', route('index'));
    }
}
