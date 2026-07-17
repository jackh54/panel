<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Editable notification email templates
    |--------------------------------------------------------------------------
    |
    | Defaults match the previous hardcoded copy. Admins can override each
    | template in Admin → Settings → Email Templates. Placeholders use {{name}}.
    |
    */
    'templates' => [
        'account_created' => [
            'label' => 'Account Created',
            'description' => 'Sent when an administrator creates a user account.',
            'enabled' => true,
            'subject' => 'Your {{app_name}} Account',
            'greeting' => 'Hello {{user_name}}!',
            'lines' => [
                'You are receiving this email because an account has been created for you on {{app_name}}.',
                'Username: {{username}}',
                'Email: {{email}}',
            ],
            'action_text' => 'Setup Your Account',
            'action_url' => '{{action_url}}',
            'level' => 'info',
            'placeholders' => ['user_name', 'username', 'email', 'app_name', 'action_url'],
        ],
        'account_suspended' => [
            'label' => 'Account Suspended',
            'description' => 'Sent when a user account is suspended.',
            'enabled' => true,
            'subject' => 'Account Suspended',
            'greeting' => 'Hello {{user_name}}!',
            'lines' => [
                'Your account has been suspended. You will not be able to sign in until an administrator unsuspends your account.',
                'Any servers you own have also been suspended and are no longer accessible.',
            ],
            'action_text' => '',
            'action_url' => '',
            'level' => 'error',
            'placeholders' => ['user_name', 'app_name'],
        ],
        'account_unsuspended' => [
            'label' => 'Account Unsuspended',
            'description' => 'Sent when a user account is unsuspended.',
            'enabled' => true,
            'subject' => 'Account Unsuspended',
            'greeting' => 'Hello {{user_name}}!',
            'lines' => [
                'Your account has been unsuspended. You may sign in again.',
            ],
            'action_text' => 'Sign In',
            'action_url' => '{{action_url}}',
            'level' => 'info',
            'placeholders' => ['user_name', 'app_name', 'action_url'],
        ],
        'added_to_server' => [
            'label' => 'Added to Server',
            'description' => 'Sent when a user is added as a subuser on a server.',
            'enabled' => true,
            'subject' => 'Added to Server',
            'greeting' => 'Hello {{user_name}}!',
            'lines' => [
                'You have been added as a subuser for the following server, allowing you certain control over the server.',
                'Server Name: {{server_name}}',
            ],
            'action_text' => 'Visit Server',
            'action_url' => '{{action_url}}',
            'level' => 'info',
            'placeholders' => ['user_name', 'server_name', 'action_url'],
        ],
        'removed_from_server' => [
            'label' => 'Removed from Server',
            'description' => 'Sent when a user is removed as a subuser from a server.',
            'enabled' => true,
            'subject' => 'Removed from Server',
            'greeting' => 'Hello {{user_name}}.',
            'lines' => [
                'You have been removed as a subuser for the following server.',
                'Server Name: {{server_name}}',
            ],
            'action_text' => 'Visit Panel',
            'action_url' => '{{action_url}}',
            'level' => 'error',
            'placeholders' => ['user_name', 'server_name', 'action_url'],
        ],
        'server_installed' => [
            'label' => 'Server Installed',
            'description' => 'Sent when a server finishes installing.',
            'enabled' => true,
            'subject' => 'Server Installed',
            'greeting' => 'Hello {{user_name}}.',
            'lines' => [
                'Your server has finished installing and is now ready for you to use.',
                'Server Name: {{server_name}}',
            ],
            'action_text' => 'Login and Begin Using',
            'action_url' => '{{action_url}}',
            'level' => 'info',
            'placeholders' => ['user_name', 'server_name', 'action_url'],
        ],
        'server_suspended' => [
            'label' => 'Server Suspended',
            'description' => 'Sent when a server is suspended.',
            'enabled' => true,
            'subject' => 'Server Suspended: {{server_name}}',
            'greeting' => 'Hello {{user_name}}!',
            'lines' => [
                'Your server has been suspended and is no longer accessible.',
                'Server Name: {{server_name}}',
            ],
            'action_text' => 'Visit Panel',
            'action_url' => '{{action_url}}',
            'level' => 'error',
            'placeholders' => ['user_name', 'server_name', 'action_url'],
        ],
        'server_unsuspended' => [
            'label' => 'Server Unsuspended',
            'description' => 'Sent when a server is unsuspended.',
            'enabled' => true,
            'subject' => 'Server Unsuspended: {{server_name}}',
            'greeting' => 'Hello {{user_name}}!',
            'lines' => [
                'Your server has been unsuspended and is available again.',
                'Server Name: {{server_name}}',
            ],
            'action_text' => 'Visit Server',
            'action_url' => '{{action_url}}',
            'level' => 'info',
            'placeholders' => ['user_name', 'server_name', 'action_url'],
        ],
        'password_reset' => [
            'label' => 'Password Reset',
            'description' => 'Sent when a user requests a password reset.',
            'enabled' => true,
            'subject' => 'Reset Password',
            'greeting' => '',
            'lines' => [
                'You are receiving this email because we received a password reset request for your account.',
                'If you did not request a password reset, no further action is required.',
            ],
            'action_text' => 'Reset Password',
            'action_url' => '{{action_url}}',
            'level' => 'info',
            'placeholders' => ['email', 'action_url', 'app_name'],
        ],
        'mail_tested' => [
            'label' => 'Mail Test',
            'description' => 'Sent from Admin → Mail → Send Test.',
            'enabled' => true,
            'subject' => '{{brand_name}} Test Message',
            'greeting' => 'Hello {{user_name}}!',
            'lines' => [
                'This is a test of the {{brand_name}} mail system. You\'re good to go!',
            ],
            'action_text' => '',
            'action_url' => '',
            'level' => 'info',
            'placeholders' => ['user_name', 'brand_name', 'app_name'],
        ],
    ],
];
