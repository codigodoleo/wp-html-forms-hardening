# WP HTML Forms Hardening

Reusable hardening package for the [HTML Forms](https://wordpress.org/plugins/html-forms/) plugin.

## Features

- Enforces HTML Forms nonce
- Enforces reCAPTCHA key resolution
- Supports key source priority: env, options, env->options
- Configurable minimum reCAPTCHA score
- Optional disabling of request-size spam validation
- Built-in admin settings page for captcha keys and behavior

## Installation

```bash
composer require codigodoleo/wp-html-forms-hardening
```

## Publish config

```bash
wp acorn vendor:publish --tag=html-forms-hardening-config
```

## Default env keys

- `GOOGLE_RECAPTCHA_SITE_KEY`
- `GOOGLE_RECAPTCHA_SECRET_KEY`
- `RECAPTCHA_SITE_KEY`
- `RECAPTCHA_SECRET_KEY`
- `WORDPRESS_GOOGLE_RECAPTCHA_SITE_KEY`
- `WORDPRESS_GOOGLE_RECAPTCHA_SECRET_KEY`

## Admin page

After activation, go to:

`Settings -> HTML Forms Security`

Configure:

- reCAPTCHA Site Key
- reCAPTCHA Secret Key
- Minimum Score
- Request-size validation toggle

## Notes

- Package is designed for WordPress + Sage/Acorn projects.
- Hardening filters are applied globally for HTML Forms hooks.
- When `key_source` is `env_then_options` (default), env vars override admin settings.
