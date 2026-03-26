<?php

namespace CodigoDoOleo\HtmlFormsHardening\Admin;

class SettingsPage
{
    /**
     * Register admin menu and settings.
     */
    public static function register(): void
    {
        add_action('admin_menu', [static::class, 'registerMenu']);
        add_action('admin_init', [static::class, 'registerSettings']);
    }

    /**
     * Register settings page menu.
     */
    public static function registerMenu(): void
    {
        $parent = (string) config('html-forms-hardening.admin_page.menu_parent', 'options-general.php');
        $slug = (string) config('html-forms-hardening.admin_page.menu_slug', 'html-forms-hardening');
        $capability = (string) config('html-forms-hardening.admin_page.capability', 'manage_options');

        add_submenu_page(
            $parent,
            __('HTML Forms Security', 'wp-html-forms-hardening'),
            __('HTML Forms Security', 'wp-html-forms-hardening'),
            $capability,
            $slug,
            [static::class, 'renderPage']
        );
    }

    /**
     * Register settings and fields.
     */
    public static function registerSettings(): void
    {
        $group = 'hfh_settings_group';

        $siteKeyOption = (string) config('html-forms-hardening.option_keys.site');
        $secretKeyOption = (string) config('html-forms-hardening.option_keys.secret');
        $minScoreOption = (string) config('html-forms-hardening.option_keys.min_score');
        $disableSizeOption = (string) config('html-forms-hardening.option_keys.disable_request_size_validation');

        register_setting($group, $siteKeyOption, ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting($group, $secretKeyOption, ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting($group, $minScoreOption, ['type' => 'number', 'sanitize_callback' => [static::class, 'sanitizeMinScore']]);
        register_setting($group, $disableSizeOption, ['type' => 'boolean', 'sanitize_callback' => [static::class, 'sanitizeCheckbox']]);

        add_settings_section(
            'hfh_security_section',
            __('reCAPTCHA + HTML Forms', 'wp-html-forms-hardening'),
            function () {
                echo '<p>' . esc_html__('Configure reCAPTCHA keys and request validation behavior for HTML Forms.', 'wp-html-forms-hardening') . '</p>';
            },
            'hfh_settings_page'
        );

        add_settings_field(
            $siteKeyOption,
            __('reCAPTCHA Site Key', 'wp-html-forms-hardening'),
            function () use ($siteKeyOption) {
                $value = (string) get_option($siteKeyOption, '');
                echo '<input type="text" class="regular-text" name="' . esc_attr($siteKeyOption) . '" value="' . esc_attr($value) . '" />';
            },
            'hfh_settings_page',
            'hfh_security_section'
        );

        add_settings_field(
            $secretKeyOption,
            __('reCAPTCHA Secret Key', 'wp-html-forms-hardening'),
            function () use ($secretKeyOption) {
                $value = (string) get_option($secretKeyOption, '');
                echo '<input type="text" class="regular-text" name="' . esc_attr($secretKeyOption) . '" value="' . esc_attr($value) . '" />';
            },
            'hfh_settings_page',
            'hfh_security_section'
        );

        add_settings_field(
            $minScoreOption,
            __('Minimum reCAPTCHA Score', 'wp-html-forms-hardening'),
            function () use ($minScoreOption) {
                $value = get_option($minScoreOption, config('html-forms-hardening.min_score', 0.7));
                echo '<input type="number" min="0" max="1" step="0.1" name="' . esc_attr($minScoreOption) . '" value="' . esc_attr((string) $value) . '" />';
            },
            'hfh_settings_page',
            'hfh_security_section'
        );

        add_settings_field(
            $disableSizeOption,
            __('Disable request-size spam validation', 'wp-html-forms-hardening'),
            function () use ($disableSizeOption) {
                $checked = (bool) get_option($disableSizeOption, config('html-forms-hardening.disable_request_size_validation', true));
                echo '<label><input type="checkbox" name="' . esc_attr($disableSizeOption) . '" value="1" ' . checked($checked, true, false) . ' /> ' . esc_html__('Recommended for forms that inject extra hidden fields.', 'wp-html-forms-hardening') . '</label>';
            },
            'hfh_settings_page',
            'hfh_security_section'
        );
    }

    /**
     * Render settings page.
     */
    public static function renderPage(): void
    {
        $group = 'hfh_settings_group';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('HTML Forms Security', 'wp-html-forms-hardening') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields($group);
        do_settings_sections('hfh_settings_page');
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    /**
     * Clamp min score to [0,1].
     */
    public static function sanitizeMinScore($value): float
    {
        $numeric = is_numeric($value) ? (float) $value : 0.7;

        if ($numeric < 0) {
            return 0.0;
        }

        if ($numeric > 1) {
            return 1.0;
        }

        return $numeric;
    }

    /**
     * Normalize checkbox.
     */
    public static function sanitizeCheckbox($value): bool
    {
        return (bool) $value;
    }
}
