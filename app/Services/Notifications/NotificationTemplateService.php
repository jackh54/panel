<?php

namespace Pterodactyl\Services\Notifications;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Notifications\Messages\MailMessage;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class NotificationTemplateService
{
    public function __construct(private SettingsRepositoryInterface $settings)
    {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $templates = [];
        foreach (array_keys(config('notifications.templates', [])) as $type) {
            $templates[$type] = $this->get($type);
        }

        return $templates;
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $type): array
    {
        $defaults = config('notifications.templates.' . $type);
        if (!is_array($defaults)) {
            throw new \InvalidArgumentException("Unknown notification template [{$type}].");
        }

        $stored = $this->settings->get('settings::notifications:templates:' . $type, null);
        $override = [];
        if (is_string($stored) && $stored !== '') {
            $decoded = json_decode($stored, true);
            if (is_array($decoded)) {
                $override = $decoded;
            }
        }

        $merged = array_merge($defaults, Arr::only($override, [
            'enabled', 'subject', 'greeting', 'lines', 'action_text', 'action_url', 'level',
        ]));

        if (isset($override['lines']) && is_array($override['lines'])) {
            $merged['lines'] = array_values(array_filter(array_map('strval', $override['lines']), fn ($line) => $line !== ''));
        }

        $merged['enabled'] = filter_var($merged['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $merged['type'] = $type;

        return $merged;
    }

    /**
     * Persist an admin override for a template type.
     *
     * @param array<string, mixed> $data
     */
    public function save(string $type, array $data): void
    {
        $this->get($type); // validate type exists

        $lines = $data['lines'] ?? [];
        if (is_string($lines)) {
            $lines = preg_split("/\r\n|\n|\r/", $lines) ?: [];
        }

        $payload = [
            'enabled' => filter_var($data['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'subject' => (string) ($data['subject'] ?? ''),
            'greeting' => (string) ($data['greeting'] ?? ''),
            'lines' => array_values(array_filter(array_map('strval', $lines), fn ($line) => trim($line) !== '')),
            'action_text' => (string) ($data['action_text'] ?? ''),
            'action_url' => (string) ($data['action_url'] ?? ''),
            'level' => in_array($data['level'] ?? 'info', ['info', 'success', 'error'], true)
                ? $data['level']
                : 'info',
        ];

        $this->settings->set('settings::notifications:templates:' . $type, json_encode($payload));
    }

    public function reset(string $type): void
    {
        $this->get($type);
        $this->settings->forget('settings::notifications:templates:' . $type);
    }

    /**
     * Build a MailMessage from a template and placeholder values.
     *
     * @param array<string, string|null> $vars
     */
    public function mailMessage(string $type, array $vars = []): ?MailMessage
    {
        $template = $this->get($type);
        if (!$template['enabled']) {
            return null;
        }

        $vars = array_merge([
            'app_name' => (string) config('brand.name', config('app.name')),
            'brand_name' => (string) config('brand.name', config('app.name')),
        ], $vars);

        $subject = $this->interpolate((string) $template['subject'], $vars);
        $greeting = $this->interpolate((string) $template['greeting'], $vars);
        $actionText = trim($this->interpolate((string) $template['action_text'], $vars));
        $actionUrl = trim($this->interpolate((string) $template['action_url'], $vars));

        $message = new MailMessage();

        if (($template['level'] ?? 'info') === 'error') {
            $message->error();
        } elseif (($template['level'] ?? '') === 'success') {
            $message->success();
        }

        if ($subject !== '') {
            $message->subject($subject);
        }

        if ($greeting !== '') {
            $message->greeting($greeting);
        }

        foreach ($template['lines'] as $line) {
            $rendered = $this->interpolate((string) $line, $vars);
            if ($rendered !== '') {
                $message->line($rendered);
            }
        }

        if ($actionText !== '' && $actionUrl !== '' && Str::startsWith($actionUrl, ['http://', 'https://', '/'])) {
            if (Str::startsWith($actionUrl, '/')) {
                $actionUrl = url($actionUrl);
            }
            $message->action($actionText, $actionUrl);
        }

        return $message;
    }

    /**
     * @param array<string, string|null> $vars
     */
    private function interpolate(string $text, array $vars): string
    {
        return (string) preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($matches) use ($vars) {
            $key = $matches[1];

            return array_key_exists($key, $vars) ? (string) ($vars[$key] ?? '') : $matches[0];
        }, $text);
    }
}
