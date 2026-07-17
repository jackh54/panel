<?php

namespace Pterodactyl\Notifications;

use Pterodactyl\Models\User;
use Illuminate\Bus\Queueable;
use Pterodactyl\Events\Event;
use Pterodactyl\Models\Server;
use Illuminate\Container\Container;
use Pterodactyl\Events\Server\Installed;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Pterodactyl\Contracts\Core\ReceivesEvents;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Notifications\Messages\MailMessage;

class ServerInstalled extends Notification implements ShouldQueue, ReceivesEvents
{
    use Queueable;
    use UsesNotificationTemplate;

    public Server $server;

    public User $user;

    /**
     * @phpstan-param Installed $event
     */
    public function handle(Event|Installed $event): void
    {
        $event->server->loadMissing('user');

        $this->server = $event->server;
        $this->user = $event->server->user;

        Container::getInstance()->make(Dispatcher::class)->sendNow($this->user, $this);
    }

    public function via(): array
    {
        return $this->notificationChannels('server_installed');
    }

    public function toMail(): MailMessage
    {
        return $this->templateMail('server_installed', [
            'user_name' => $this->user->username,
            'server_name' => $this->server->name,
            'action_url' => route('index'),
        ]);
    }
}
