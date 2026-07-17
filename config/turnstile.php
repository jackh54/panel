<?php

return [
    /*
     * Enable or disable Cloudflare Turnstile.
     * Prefer disabled until site/secret keys are configured.
     */
    'enabled' => env('TURNSTILE_ENABLED', false),

    /*
     * Cloudflare siteverify endpoint.
     */
    'domain' => env('TURNSTILE_DOMAIN', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),

    /*
     * Widget site key (public).
     */
    'site_key' => env('TURNSTILE_SITE_KEY', ''),
    'website_key' => env('TURNSTILE_SITE_KEY', ''),

    /*
     * Secret key (private).
     */
    'secret_key' => env('TURNSTILE_SECRET_KEY', ''),
];
