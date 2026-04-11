<?php

/**
 * Settings Helper
 * Provides a global setting() function to fetch CMS settings from the DB.
 * Values are cached in a static array for the request lifetime.
 */

if (! function_exists('setting')) {
    function setting(string $key, string $default = ''): string
    {
        static $cache = null;

        if ($cache === null) {
            try {
                $model = new \App\Models\SettingModel();
                $cache = $model->getAllKeyed();
            } catch (\Throwable $e) {
                $cache = [];
            }
        }

        return isset($cache[$key]) && $cache[$key] !== null
            ? $cache[$key]
            : $default;
    }
}

if (! function_exists('setting_logo_url')) {
    /** Returns the logo URL, or null if no logo is set. */
    function setting_logo_url(): ?string
    {
        $path = setting('logo_path');
        if ($path && file_exists(FCPATH . $path)) {
            return base_url($path);
        }
        return null;
    }
}
