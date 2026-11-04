<?php
// helpers/report_helper.php

/**
 * Common chart generation helpers or data formatting for reports
 */
function calculatePercentage($part, $total) {
    if ($total == 0) return 0;
    return round(($part / $total) * 100, 2);
}

function getStatusBadgeClass($status) {
    switch(strtolower($status)) {
        case 'completed': return 'bg-success';
        case 'pending': return 'bg-warning text-dark';
        case 'scheduled': return 'bg-info text-dark';
        default: return 'bg-secondary';
    }
}
