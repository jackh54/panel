<?php

namespace Pterodactyl\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Pterodactyl\Services\Notifications\NotificationTemplateService;

trait UsesNotificationTemplate
{
    protected function notificationChannels(string $type): array
    {
        return $this->templates()->get($type)['enabled'] ? ['mail'] : [];
    }

    /**
     * @param array<string, string|null> $vars
     */
    protected function templateMail(string $type, array $vars = []): MailMessage
    {
        $message = $this->templates()->mailMessage($type, $vars);
        if ($message === null) {
            return (new MailMessage())->subject('Notification disabled');
        }

        return $message;
    }

    private function templates(): NotificationTemplateService
    {
        return app(NotificationTemplateService::class);
    }
}
