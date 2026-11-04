<?php
// tests/Unit/MaintenanceCalculatorTest.php
namespace Tests\Unit;

use Tests\TestCase;

require_once __DIR__ . '/../../helpers/maintenance_calculator.php';

class MaintenanceCalculatorTest extends TestCase {

    public function testOverdueDateTakesPrecedence() {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $eval = \MaintenanceCalculator::evaluate($yesterday, null, 10000, 7, 500);

        $this->assertEquals(\MaintenanceCalculator::STATUS_OVERDUE, $eval['status']);
        $this->assertStringContainsString('past due date', $eval['reason']);
        $this->assertTrue($eval['days_diff'] < 0);
    }

    public function testOverdueMileageTakesPrecedence() {
        // Blueprint Edge Case (Page 12): Date is 7 days in the future, but mileage already exceeded
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        $eval = \MaintenanceCalculator::evaluate($nextWeek, 50000, 52000, 7, 500);

        $this->assertEquals(\MaintenanceCalculator::STATUS_OVERDUE, $eval['status']);
        $this->assertStringContainsString('past target mileage', $eval['reason']);
        $this->assertTrue($eval['km_diff'] < 0);
    }

    public function testDueTodayDate() {
        $today = date('Y-m-d');
        $eval = \MaintenanceCalculator::evaluate($today, null, 10000, 7, 500);

        $this->assertEquals(\MaintenanceCalculator::STATUS_DUE, $eval['status']);
        $this->assertStringContainsString('Due today', $eval['reason']);
        $this->assertEquals(0, $eval['days_diff']);
    }

    public function testDueExactDistanceReached() {
        $nextMonth = date('Y-m-d', strtotime('+30 days'));
        $eval = \MaintenanceCalculator::evaluate($nextMonth, 60000, 60000, 7, 500);

        $this->assertEquals(\MaintenanceCalculator::STATUS_DUE, $eval['status']);
        $this->assertStringContainsString('Target mileage reached', $eval['reason']);
        $this->assertEquals(0, $eval['km_diff']);
    }

    public function testUpcomingWithinDateWindow() {
        $inFourDays = date('Y-m-d', strtotime('+4 days'));
        $eval = \MaintenanceCalculator::evaluate($inFourDays, null, 10000, 7, 500);

        $this->assertEquals(\MaintenanceCalculator::STATUS_UPCOMING, $eval['status']);
        $this->assertStringContainsString('Due in 4 day(s)', $eval['reason']);
    }

    public function testUpcomingWithinDistanceWindow() {
        $inTwoMonths = date('Y-m-d', strtotime('+60 days'));
        // 300 km remaining (within 500 km window)
        $eval = \MaintenanceCalculator::evaluate($inTwoMonths, 45000, 44700, 7, 500);

        $this->assertEquals(\MaintenanceCalculator::STATUS_UPCOMING, $eval['status']);
        $this->assertStringContainsString('Due in 300 km', $eval['reason']);
    }

    public function testNotDueWhenOutsideAllThresholds() {
        $inThreeMonths = date('Y-m-d', strtotime('+90 days'));
        $eval = \MaintenanceCalculator::evaluate($inThreeMonths, 70000, 50000, 7, 500);

        $this->assertEquals(\MaintenanceCalculator::STATUS_NOT_DUE, $eval['status']);
    }

    public function testInactiveSchedule() {
        $eval = \MaintenanceCalculator::evaluate(date('Y-m-d'), 50000, 50000, 7, 500, 'completed');
        $this->assertEquals(\MaintenanceCalculator::STATUS_INACTIVE, $eval['status']);
    }
}

