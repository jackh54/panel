<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;
use Pterodactyl\Services\Helpers\AssetHashService;

class AssetComposer
{
    /**
     * AssetComposer constructor.
     */
    public function __construct(private AssetHashService $assetHashService)
    {
    }

    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view): void
    {
        $view->with('asset', $this->assetHashService);
        $view->with('siteConfiguration', [
            'name' => config('brand.name') ?? config('app.name') ?? 'PandaScript',
            'locale' => config('app.locale') ?? 'en',
            'turnstile' => [
                'enabled' => (bool) config('turnstile.enabled', false),
                'siteKey' => config('turnstile.site_key') ?: config('turnstile.website_key') ?: '',
            ],
            'recaptcha' => [
                'enabled' => (bool) config('turnstile.enabled', false),
                'siteKey' => config('turnstile.site_key') ?: config('turnstile.website_key') ?: '',
            ],
            'branding' => [
                'logo' => config('brand.logo_path') ?: '/branding/logo.png',
                'company' => config('brand.company'),
                'url' => config('brand.url'),
                'links' => $this->brandingLinks(),
            ],
            'announcement' => [
                'enabled' => (bool) config('brand.announcement_enabled', false)
                    && trim((string) config('brand.announcement_message', '')) !== '',
                'message' => trim((string) config('brand.announcement_message', '')),
                'type' => in_array(config('brand.announcement_type'), ['info', 'warning', 'danger'], true)
                    ? config('brand.announcement_type')
                    : 'info',
            ],
            'sentry' => [
                'dsn' => ($dsn = trim((string) config('sentry-frontend.dsn'))) !== '' ? $dsn : null,
                'environment' => config('sentry.environment') ?: config('app.env'),
                'release' => sentry_release(),
                'tracesSampleRate' => (float) config('sentry-frontend.traces_sample_rate', 0.1),
                'replaysSessionSampleRate' => (float) config('sentry-frontend.replays_session_sample_rate', 0.0),
                'replaysOnErrorSampleRate' => (float) config('sentry-frontend.replays_on_error_sample_rate', 1.0),
            ],
        ]);
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function brandingLinks(): array
    {
        $slots = [
            ['label' => config('brand.link_docs_label'), 'url' => config('brand.link_docs_url')],
            ['label' => config('brand.link_discord_label'), 'url' => config('brand.link_discord_url')],
            ['label' => config('brand.link_billing_label'), 'url' => config('brand.link_billing_url')],
        ];

        $links = [];
        foreach ($slots as $slot) {
            $url = trim((string) ($slot['url'] ?? ''));
            $label = trim((string) ($slot['label'] ?? ''));
            if ($url === '' || $label === '') {
                continue;
            }
            $links[] = ['label' => $label, 'url' => $url];
        }

        return $links;
    }
}
