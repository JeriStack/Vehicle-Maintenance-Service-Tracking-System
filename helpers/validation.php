<?php
// helpers/validation.php

/**
 * Validates an email address.
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validates a required string length.
 */
function isValidLength($string, $min = 1, $max = 255) {
    $length = strlen(trim($string));
    return $length >= $min && $length <= $max;
}

/**
 * Sanitizes a string for safe output (prevent XSS)
 */
function sanitizeOutput($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Validates if an input is a positive number
 */
function isPositiveNumber($number) {
    return is_numeric($number) && $number >= 0;
}
