<?php

namespace CodigoDoOleo\HtmlFormsHardening\Providers;

use CodigoDoOleo\HtmlFormsHardening\Admin\SettingsPage;
use Illuminate\Support\ServiceProvider;

class HtmlFormsHardeningServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/html-forms-hardening.php',
            'html-forms-hardening'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/html-forms-hardening.php' => config_path('html-forms-hardening.php'),
            ], 'html-forms-hardening-config');
        }

        if ((bool) config('html-forms-hardening.admin_page.enabled', true)) {
            SettingsPage::register();
        }

        $this->registerFilters();
        add_action('init', [$this, 'bootstrapHtmlFormsRuntime'], 1);
    }

    /**
     * Ensure HTML Forms runtime sees resolved keys even when initialized before Acorn providers.
     */
    public function bootstrapHtmlFormsRuntime(): void
    {
        $this->syncHtmlFormsSettingsOption();
        $this->ensureRecaptchaHooks();
    }

    /**
     * Register HTML Forms hardening filters.
     */
    protected function registerFilters(): void
    {
        add_filter('hf_settings', function (array $settings): array {
            $siteKey = $this->resolveRecaptchaKey('site');
            $secretKey = $this->resolveRecaptchaKey('secret');

            if ((bool) config('html-forms-hardening.enable_nonce', true)) {
                $settings['enable_nonce'] = 1;
            }

            if (! isset($settings['google_recaptcha']) || ! is_array($settings['google_recaptcha'])) {
                $settings['google_recaptcha'] = [];
            }

            $settings['google_recaptcha']['site_key'] = $siteKey;
            $settings['google_recaptcha']['secret_key'] = $secretKey;

            return $settings;
        });

        add_filter('hf_recaptcha_min_score', function ($minScore): float {
            $configured = $this->getOption('min_score');

            if (is_numeric($configured)) {
                return (float) $configured;
            }

            return (float) config('html-forms-hardening.min_score', 0.7);
        });

        add_filter('hf_validate_form_request_size', function ($enabled) {
            $configured = $this->getOption('disable_request_size_validation');

            if ($configured !== null) {
                return ! (bool) $configured;
            }

            return ! (bool) config('html-forms-hardening.disable_request_size_validation', true);
        });

        add_filter('hf_validate_form', function ($errorCode, $form, array $data) {
            if (! empty($errorCode)) {
                return $errorCode;
            }

            $siteKey = $this->resolveRecaptchaKey('site');
            $secretKey = $this->resolveRecaptchaKey('secret');

            if ($siteKey === '' || $secretKey === '') {
                return 'recaptcha_failed';
            }

            if (empty($data['g-recaptcha-response'])) {
                return 'recaptcha_failed';
            }

            return $errorCode;
        }, 5, 3);
    }

    /**
     * Persist resolved settings so HTML Forms can consume them outside the filter lifecycle.
     */
    protected function syncHtmlFormsSettingsOption(): void
    {
        if (! function_exists('get_option') || ! function_exists('update_option')) {
            return;
        }

        $siteKey = $this->resolveRecaptchaKey('site');
        $secretKey = $this->resolveRecaptchaKey('secret');

        if ($siteKey === '' || $secretKey === '') {
            return;
        }

        $settings = get_option('hf_settings', []);

        if (! is_array($settings)) {
            $settings = [];
        }

        if ((bool) config('html-forms-hardening.enable_nonce', true)) {
            $settings['enable_nonce'] = 1;
        }

        if (! isset($settings['google_recaptcha']) || ! is_array($settings['google_recaptcha'])) {
            $settings['google_recaptcha'] = [];
        }

        $currentSiteKey = trim((string) ($settings['google_recaptcha']['site_key'] ?? ''));
        $currentSecretKey = trim((string) ($settings['google_recaptcha']['secret_key'] ?? ''));

        if ($currentSiteKey === $siteKey && $currentSecretKey === $secretKey) {
            return;
        }

        $settings['google_recaptcha']['site_key'] = $siteKey;
        $settings['google_recaptcha']['secret_key'] = $secretKey;

        update_option('hf_settings', $settings, true);
    }

    /**
     * Register reCAPTCHA hooks when HTML Forms initialized before hardening filters were attached.
     */
    protected function ensureRecaptchaHooks(): void
    {
        if (! class_exists('HTML_Forms\\Admin\\Recaptcha') || ! function_exists('hf_get_settings')) {
            return;
        }

        $settings = hf_get_settings();
        $siteKey = trim((string) ($settings['google_recaptcha']['site_key'] ?? ''));
        $secretKey = trim((string) ($settings['google_recaptcha']['secret_key'] ?? ''));

        if ($siteKey === '' || $secretKey === '') {
            return;
        }

        if (has_filter('hf_form_html') && has_filter('hf_validate_form')) {
            return;
        }

        $recaptcha = new \HTML_Forms\Admin\Recaptcha();
        $recaptcha->hook();
    }

    /**
     * Resolve key based on configured source strategy.
     */
    protected function resolveRecaptchaKey(string $type): string
    {
        $source = (string) config('html-forms-hardening.key_source', 'env_then_options');

        if ($source === 'env_only') {
            return $this->getEnvKey($type);
        }

        if ($source === 'options_only') {
            return $this->getOptionKey($type);
        }

        $env = $this->getEnvKey($type);

        if ($env !== '') {
            return $env;
        }

        return $this->getOptionKey($type);
    }

    /**
     * Read one key value from env candidates.
     */
    protected function getEnvKey(string $type): string
    {
        $candidates = (array) config("html-forms-hardening.env_keys.{$type}", []);

        foreach ($candidates as $candidate) {
            $value = getenv($candidate);

            if ($value === false && isset($_ENV[$candidate])) {
                $value = $_ENV[$candidate];
            }

            if ($value === false && isset($_SERVER[$candidate])) {
                $value = $_SERVER[$candidate];
            }

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    /**
     * Read one key value from stored options.
     */
    protected function getOptionKey(string $type): string
    {
        $optionName = (string) config("html-forms-hardening.option_keys.{$type}", '');

        if ($optionName === '') {
            return '';
        }

        $value = get_option($optionName, '');

        return is_string($value) ? trim($value) : '';
    }

    /**
     * Read generic option value.
     */
    protected function getOption(string $type)
    {
        $optionName = (string) config("html-forms-hardening.option_keys.{$type}", '');

        if ($optionName === '') {
            return null;
        }

        return get_option($optionName, null);
    }
}
