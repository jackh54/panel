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
    use UsesNotificationTemplate;

    public function __construct(public Server $server)
    {
        $this->afterCommit();
    }

    public function via(): array
    {
        return $this->notificationChannels('server_suspended');
    }

    public function toMail(): MailMessage
    {
        return $this->templateMail('server_suspended', [
            'user_name' => $this->server->user->name,
            'server_name' => $this->server->name,
            'action_url' => route('index'),
        ]);
    }
}
