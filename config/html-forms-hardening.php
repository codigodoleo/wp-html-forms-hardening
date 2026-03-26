<?php

return [
    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA key source
    |--------------------------------------------------------------------------
    |
    | env_then_options: first read env vars, then Settings page options
    | env_only: only read env vars
    | options_only: only read Settings page options
    |
    */
    'key_source' => 'env_then_options',

    /*
    |--------------------------------------------------------------------------
    | Environment key candidates
    |--------------------------------------------------------------------------
    */
    'env_keys' => [
        'site' => [
            'GOOGLE_RECAPTCHA_SITE_KEY',
            'RECAPTCHA_SITE_KEY',
            'WORDPRESS_GOOGLE_RECAPTCHA_SITE_KEY',
            'WORDPRESS_RECAPTCHA_SITE_KEY',
        ],
        'secret' => [
            'GOOGLE_RECAPTCHA_SECRET_KEY',
            'RECAPTCHA_SECRET_KEY',
            'WORDPRESS_GOOGLE_RECAPTCHA_SECRET_KEY',
            'WORDPRESS_RECAPTCHA_SECRET_KEY',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security behavior
    |--------------------------------------------------------------------------
    */
    'enable_nonce' => true,
    'min_score' => 0.7,
    'disable_request_size_validation' => true,

    /*
    |--------------------------------------------------------------------------
    | Admin settings page
    |--------------------------------------------------------------------------
    */
    'admin_page' => [
        'enabled' => true,
        'menu_parent' => 'options-general.php',
        'menu_slug' => 'html-forms-hardening',
        'capability' => 'manage_options',
    ],

    /*
    |--------------------------------------------------------------------------
    | Option keys used by settings page
    |--------------------------------------------------------------------------
    */
    'option_keys' => [
        'site' => 'hfh_recaptcha_site_key',
        'secret' => 'hfh_recaptcha_secret_key',
        'min_score' => 'hfh_recaptcha_min_score',
        'disable_request_size_validation' => 'hfh_disable_request_size_validation',
    ],
];
