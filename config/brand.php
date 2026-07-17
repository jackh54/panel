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
    | `logo` should be an absolute URL for emails (clients block relative images).
    | `logo_path` is the panel-served asset used in the React UI.
    |
    */
    'logo' => env('APP_LOGO', 'https://r.pandascript.dev/images/PandaScript.png'),
    'logo_path' => env('APP_LOGO_PATH', '/assets/branding/logo.png'),
];
