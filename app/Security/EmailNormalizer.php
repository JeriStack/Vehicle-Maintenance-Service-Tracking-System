<?php
declare(strict_types=1);

namespace VMS\Security;

/**
 * Class EmailNormalizer
 *
 * Provides canonical, uniform email normalization and validation across all application boundaries.
 */
class EmailNormalizer
{
    /**
     * Canonical email normalization:
     * - Trims surrounding whitespace
     * - Converts to lowercase via UTF-8 mb_strtolower
     * - Strips unprintable control characters
     */
    public static function normalize(?string $email): string
    {
        if ($email === null) {
            return '';
        }

        $trimmed = trim($email);
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $trimmed) ?? $trimmed;
        return mb_strtolower($clean, 'UTF-8');
    }

    /**
     * Validates email syntax against standard RFC specifications.
     */
    public static function isValid(?string $email): bool
    {
        if ($email === null) {
            return false;
        }

        $normalized = self::normalize($email);
        if ($normalized === '' || strlen($normalized) > 254) {
            return false;
        }

        return (bool) filter_var($normalized, FILTER_VALIDATE_EMAIL);
    }
}
