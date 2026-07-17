<?php

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AddedToServer extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesNotificationTemplate;

    public object $server;

    public function __construct(array $server)
    {
        $this->server = (object) $server;
    }

    public function via(): array
    {
        return $this->notificationChannels('added_to_server');
    }

    public function toMail(): MailMessage
    {
        return $this->templateMail('added_to_server', [
            'user_name' => $this->server->user,
            'server_name' => $this->server->name,
            'action_url' => url('/server/' . $this->server->uuidShort),
        ]);
    }
}
