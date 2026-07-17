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
];
