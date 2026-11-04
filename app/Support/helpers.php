<?php
declare(strict_types=1);

// app/Support/helpers.php

use VMS\Core\Csrf;
use VMS\Core\Session;
use VMS\Core\Config;

if (!function_exists('e')) {
    /**
     * Escape HTML output safely using ENT_QUOTES and UTF-8.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the active CSRF token.
     */
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Render an HTML hidden CSRF token input field.
     */
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('old')) {
    /**
     * Retrieve previously submitted form input from session or fallback.
     */
    function old(string $key, mixed $default = ''): mixed
    {
        $oldInputs = Session::get('_vms_old_inputs', []);
        return $oldInputs[$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    /**
     * Set or retrieve a flash message.
     */
    function flash(?string $key = null, ?string $message = null): mixed
    {
        if ($key === null) {
            return Session::get('_vms_flash_messages', []);
        }

        if ($message === null) {
            return Session::getFlash($key);
        }

        Session::flash($key, $message);
        return null;
    }
}

if (!function_exists('app_url')) {
    /**
     * Generate an absolute application URL.
     */
    function app_url(string $path = ''): string
    {
        $base = rtrim((string) Config::get('app.url', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}
