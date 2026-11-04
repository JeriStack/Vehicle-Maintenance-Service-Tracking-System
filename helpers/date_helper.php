<?php
// helpers/date_helper.php

/**
 * Format a date string into a human readable format
 */
function formatDate($dateString, $format = 'M d, Y') {
    if (empty($dateString)) return '-';
    return date($format, strtotime($dateString));
}

/**
 * Calculate the difference in days from today
 */
function getDaysDifference($dateString) {
    if (empty($dateString)) return null;
    $targetTime = strtotime($dateString);
    $diff = $targetTime - time();
    return (int)ceil($diff / (60 * 60 * 24));
}

/**
 * Check if a date is in the past
 */
function isPastDate($dateString) {
    if (empty($dateString)) return false;
    return strtotime($dateString) < time();
}
