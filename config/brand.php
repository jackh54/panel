<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Brand display name
    |--------------------------------------------------------------------------
    */
    'name' => env('APP_NAME', 'PandaScript'),

    /*
    |--------------------------------------------------------------------------
    | Company / legal line
    |--------------------------------------------------------------------------
    */
    'company' => env('APP_COMPANY', 'PandaScript'),
    'url' => env('APP_COMPANY_URL', 'https://pandascript.dev'),

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    |
    | logo_path is served by the panel (tracked in git at public/branding/logo.png).
    | logo is an optional absolute URL override (emails need an absolute URL; if
    | unset we generate one from APP_URL + logo_path).
    |
    */
    'logo' => env('APP_LOGO'),
    'logo_path' => env('APP_LOGO_PATH', '/branding/logo.png'),

    /*
    |--------------------------------------------------------------------------
    | Custom footer / login links
    |--------------------------------------------------------------------------
    |
    | Optional links shown on the login page and client panel footer.
    | Leave URL empty to hide a slot. Overridable from Admin → Settings → Branding.
    |
    */
    'link_docs_label' => env('BRAND_LINK_DOCS_LABEL', 'Docs'),
    'link_docs_url' => env('BRAND_LINK_DOCS_URL', ''),
    'link_discord_label' => env('BRAND_LINK_DISCORD_LABEL', 'Discord'),
    'link_discord_url' => env('BRAND_LINK_DISCORD_URL', ''),
    'link_billing_label' => env('BRAND_LINK_BILLING_LABEL', 'Billing'),
    'link_billing_url' => env('BRAND_LINK_BILLING_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Client announcement banner
    |--------------------------------------------------------------------------
    */
    'announcement_enabled' => env('BRAND_ANNOUNCEMENT_ENABLED', false),
    'announcement_message' => env('BRAND_ANNOUNCEMENT_MESSAGE', ''),
    'announcement_type' => env('BRAND_ANNOUNCEMENT_TYPE', 'info'),
];
