<?php
// tests/Unit/ReportHelperTest.php
namespace Tests\Unit;

use Tests\TestCase;

require_once __DIR__ . '/../../helpers/report_helper.php';

class ReportHelperTest extends TestCase {

    public function testCalculatePercentage() {
        $this->assertEquals(50.0, calculatePercentage(50, 100));
        $this->assertEquals(25.0, calculatePercentage(1, 4));
        $this->assertEquals(33.33, calculatePercentage(1, 3));
        $this->assertEquals(100.0, calculatePercentage(500, 500));
    }

    public function testCalculatePercentageZeroTotalPreventsDivisionByZero() {
        $this->assertEquals(0, calculatePercentage(10, 0));
        $this->assertEquals(0, calculatePercentage(0, 0));
    }

    public function testGetStatusBadgeClass() {
        $this->assertEquals('bg-success', getStatusBadgeClass('Completed'));
        $this->assertEquals('bg-success', getStatusBadgeClass('completed'));
        $this->assertEquals('bg-warning text-dark', getStatusBadgeClass('Pending'));
        $this->assertEquals('bg-info text-dark', getStatusBadgeClass('Scheduled'));
        $this->assertEquals('bg-secondary', getStatusBadgeClass('UnknownStatus'));
        $this->assertEquals('bg-secondary', getStatusBadgeClass(''));
    }
}

