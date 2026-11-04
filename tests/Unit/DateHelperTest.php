<?php
// tests/Unit/DateHelperTest.php
namespace Tests\Unit;

use Tests\TestCase;

require_once __DIR__ . '/../../helpers/date_helper.php';

class DateHelperTest extends TestCase {

    public function testFormatDateStandard() {
        $formatted = formatDate('2026-12-25');
        $this->assertEquals('Dec 25, 2026', $formatted);

        $customFormat = formatDate('2026-05-15', 'Y/m/d');
        $this->assertEquals('2026/05/15', $customFormat);
    }

    public function testFormatDateEmptyOrNull() {
        $this->assertEquals('-', formatDate(''));
        $this->assertEquals('-', formatDate(null));
    }

    public function testGetDaysDifference() {
        $this->assertNull(getDaysDifference(''));
        $this->assertNull(getDaysDifference(null));

        // Future date: 10 days ahead
        $futureDate = date('Y-m-d H:i:s', strtotime('+10 days'));
        $diff = getDaysDifference($futureDate);
        $this->assertTrue($diff >= 9 && $diff <= 11);

        // Past date: 5 days ago
        $pastDate = date('Y-m-d H:i:s', strtotime('-5 days'));
        $pastDiff = getDaysDifference($pastDate);
        $this->assertTrue($pastDiff <= -4);
    }

    public function testIsPastDate() {
        $this->assertFalse(isPastDate(''));
        $this->assertFalse(isPastDate(null));

        $past = date('Y-m-d', strtotime('-10 days'));
        $this->assertTrue(isPastDate($past));

        $future = date('Y-m-d', strtotime('+10 days'));
        $this->assertFalse(isPastDate($future));
    }
}

