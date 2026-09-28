<?php
// includes/functions.php
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/date_helper.php';

/**
 * Common formatting for currency
 */
function formatCurrency($amount) {
    return '$' . number_format((float)$amount, 2);
}

/**
 * Return a safe string representation
 */
function safeString($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}
