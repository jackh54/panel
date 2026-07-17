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
            'name' => config('app.name') ?? 'Pterodactyl',
            'locale' => config('app.locale') ?? 'en',
            'recaptcha' => [
                'enabled' => config('recaptcha.enabled', false),
                'siteKey' => config('recaptcha.website_key') ?? '',
            ],
            'sentry' => [
                'dsn' => config('sentry.frontend_dsn') ?: null,
                'environment' => config('sentry.environment'),
                'release' => config('sentry.release') ?: null,
                'tracesSampleRate' => config('sentry.traces_sample_rate', 0.1),
                'replaysSessionSampleRate' => config('sentry.replays_session_sample_rate', 0.0),
                'replaysOnErrorSampleRate' => config('sentry.replays_on_error_sample_rate', 1.0),
            ],
        ]);
    }
}
