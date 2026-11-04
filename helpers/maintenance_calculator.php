<?php
// helpers/maintenance_calculator.php
/**
 * Vehicle Maintenance Service Tracking System - Maintenance Status Calculator
 * Implements the centralized "whichever comes first" maintenance status rule
 * from the VMS Blueprint (System Plan Section 6.2 & 6.3).
 */

class MaintenanceCalculator {

    const STATUS_OVERDUE   = 'OVERDUE';
    const STATUS_DUE       = 'DUE';
    const STATUS_UPCOMING  = 'UPCOMING';
    const STATUS_NOT_DUE   = 'NOT_DUE';
    const STATUS_INACTIVE  = 'INACTIVE';

    /**
     * Calculate maintenance status for a given schedule and vehicle state
     *
     * @param string|null $dueDate YYYY-MM-DD
     * @param int|null $dueOdometerKm Target mileage in km
     * @param int $currentOdometerKm Vehicle's current mileage in km
     * @param int $dateWarningDays Lead days window (default: 7)
     * @param int $distanceWarningKm Lead distance window in km (default: 500)
     * @param string $scheduleStatus Lifecycle status ('active', 'completed', 'cancelled')
     * @param string|null $todayOverride Optional date for testing (YYYY-MM-DD)
     * @return array ['status' => string, 'reason' => string, 'days_diff' => int|null, 'km_diff' => int|null]
     */
    public static function evaluate(
        $dueDate,
        $dueOdometerKm,
        $currentOdometerKm = 0,
        $dateWarningDays = 7,
        $distanceWarningKm = 500,
        $scheduleStatus = 'active',
        $todayOverride = null
    ) {
        if ($scheduleStatus !== 'active') {
            return [
                'status'    => self::STATUS_INACTIVE,
                'reason'    => 'Schedule is not active (' . $scheduleStatus . ')',
                'days_diff' => null,
                'km_diff'   => null
            ];
        }

        $todayStr = $todayOverride ?: date('Y-m-d');
        $todayTime = strtotime($todayStr);

        $daysDiff = null;
        if (!empty($dueDate)) {
            $targetTime = strtotime($dueDate);
            $daysDiff = (int)round(($targetTime - $todayTime) / 86400);
        }

        $kmDiff = null;
        if ($dueOdometerKm !== null && $dueOdometerKm > 0) {
            $kmDiff = (int)$dueOdometerKm - (int)$currentOdometerKm;
        }

        // Neither trigger specified
        if ($daysDiff === null && $kmDiff === null) {
            return [
                'status'    => self::STATUS_NOT_DUE,
                'reason'    => 'No date or mileage trigger configured',
                'days_diff' => null,
                'km_diff'   => null
            ];
        }

        // 1. OVERDUE check: at least one configured trigger has been passed
        $passedDate     = ($daysDiff !== null && $daysDiff < 0);
        $passedDistance = ($kmDiff !== null && $kmDiff < 0);

        if ($passedDate || $passedDistance) {
            $reasons = [];
            if ($passedDate) {
                $reasons[] = abs($daysDiff) . " day(s) past due date";
            }
            if ($passedDistance) {
                $reasons[] = abs($kmDiff) . " km past target mileage";
            }
            return [
                'status'    => self::STATUS_OVERDUE,
                'reason'    => implode(', ', $reasons),
                'days_diff' => $daysDiff,
                'km_diff'   => $kmDiff
            ];
        }

        // 2. DUE check: today is due date OR odometer reached target
        $reachedDate     = ($daysDiff !== null && $daysDiff === 0);
        $reachedDistance = ($kmDiff !== null && $kmDiff === 0);

        if ($reachedDate || $reachedDistance) {
            $reasons = [];
            if ($reachedDate) {
                $reasons[] = "Due today";
            }
            if ($reachedDistance) {
                $reasons[] = "Target mileage reached ({$dueOdometerKm} km)";
            }
            return [
                'status'    => self::STATUS_DUE,
                'reason'    => implode(' & ', $reasons),
                'days_diff' => $daysDiff,
                'km_diff'   => $kmDiff
            ];
        }

        // 3. UPCOMING check: within warning window
        $withinDateWindow     = ($daysDiff !== null && $daysDiff > 0 && $daysDiff <= (int)$dateWarningDays);
        $withinDistanceWindow = ($kmDiff !== null && $kmDiff > 0 && $kmDiff <= (int)$distanceWarningKm);

        if ($withinDateWindow || $withinDistanceWindow) {
            $reasons = [];
            if ($withinDateWindow) {
                $reasons[] = "Due in {$daysDiff} day(s)";
            }
            if ($withinDistanceWindow) {
                $reasons[] = "Due in {$kmDiff} km";
            }
            return [
                'status'    => self::STATUS_UPCOMING,
                'reason'    => implode(' or ', $reasons),
                'days_diff' => $daysDiff,
                'km_diff'   => $kmDiff
            ];
        }

        // 4. NOT DUE
        return [
            'status'    => self::STATUS_NOT_DUE,
            'reason'    => 'Outside threshold warning windows',
            'days_diff' => $daysDiff,
            'km_diff'   => $kmDiff
        ];
    }
}

