<?php
declare(strict_types=1);

namespace VMS\Core;

/**
 * Class Csrf
 *
 * Cryptographic Cross-Site Request Forgery (CSRF) protection foundation.
 */
class Csrf
{
    private const SESSION_TOKEN_KEY = '_csrf_token';
    private const LEGACY_TOKEN_KEY = 'csrf_token';

    /**
     * Retrieve the active CSRF token, generating one if none exists.
     *
     * @return string 64-character hexadecimal token
     */
    public static function token(): string
    {
        Session::start();
        $token = Session::get(self::SESSION_TOKEN_KEY);

        if (!is_string($token) || strlen($token) !== 64) {
            $legacy = Session::get(self::LEGACY_TOKEN_KEY);
            if (is_string($legacy) && strlen($legacy) === 64) {
                $token = $legacy;
            } else {
                $token = bin2hex(random_bytes(32));
            }
            Session::set(self::SESSION_TOKEN_KEY, $token);
        }

        // Keep legacy session key synchronized for older templates
        Session::set(self::LEGACY_TOKEN_KEY, $token);

        return $token;
    }

    /**
     * Render an HTML hidden input element containing the current CSRF token.
     *
     * @return string
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Validate an untrusted token against the session token using constant-time comparison.
     *
     * @param string|null $submittedToken
     * @return bool
     */
    public static function validate(?string $submittedToken): bool
    {
        if ($submittedToken === null || $submittedToken === '') {
            return false;
        }

        Session::start();
        $sessionToken = Session::get(self::SESSION_TOKEN_KEY);

        if (!is_string($sessionToken) || $sessionToken === '') {
            $sessionToken = Session::get(self::LEGACY_TOKEN_KEY);
        }

        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $submittedToken);
    }

    /**
     * Force rotation of the CSRF token (recommended upon authentication).
     *
     * @return string The newly generated token
     */
    public static function rotate(): string
    {
        Session::start();
        $token = bin2hex(random_bytes(32));
        Session::set(self::SESSION_TOKEN_KEY, $token);
        Session::set(self::LEGACY_TOKEN_KEY, $token);
        return $token;
    }
}
