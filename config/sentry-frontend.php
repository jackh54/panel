<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Frontend (browser) Sentry DSN
    |--------------------------------------------------------------------------
    |
    | Used by the React client panel. Leave empty to disable browser reporting.
    | Injected at runtime via SiteConfiguration — no asset rebuild needed.
    |
    | Accepted env keys (first match wins):
    |   SENTRY_FRONTEND_DSN, SENTRY_DSN_FRONTEND, SENTRY_DSN
    |
    | Prefer a dedicated browser/React project DSN. Backend PHP uses
    | SENTRY_LARAVEL_DSN via config/sentry.php.
    |
    */
    'dsn' => env('SENTRY_FRONTEND_DSN', env('SENTRY_DSN_FRONTEND', env('SENTRY_DSN'))),

    /*
    |--------------------------------------------------------------------------
    | Sample rates (frontend)
    |--------------------------------------------------------------------------
    */
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
    'replays_session_sample_rate' => (float) env('SENTRY_REPLAYS_SESSION_SAMPLE_RATE', 0.0),
    'replays_on_error_sample_rate' => (float) env('SENTRY_REPLAYS_ON_ERROR_SAMPLE_RATE', 1.0),
];
