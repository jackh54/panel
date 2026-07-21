<?php

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AdminUserMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $emailSubject,
        public string $emailBody,
    ) {
        $this->afterCommit();
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $message = (new MailMessage())
            ->subject($this->emailSubject)
            ->greeting('Hello!');

        foreach (preg_split("/\r\n|\n|\r/", $this->emailBody) ?: [] as $line) {
            $message->line($line);
        }

        return $message;
    }
}
