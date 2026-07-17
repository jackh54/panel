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
            ],
            'sentry' => [
                'dsn' => ($dsn = trim((string) config('sentry.frontend_dsn'))) !== '' ? $dsn : null,
                'environment' => config('sentry.environment'),
                'release' => config('sentry.release') ?: null,
                'tracesSampleRate' => config('sentry.traces_sample_rate', 0.1),
                'replaysSessionSampleRate' => config('sentry.replays_session_sample_rate', 0.0),
                'replaysOnErrorSampleRate' => config('sentry.replays_on_error_sample_rate', 1.0),
            ],
        ]);
    }
}
