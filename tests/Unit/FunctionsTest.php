<?php
// tests/Unit/FunctionsTest.php
namespace Tests\Unit;

use Tests\TestCase;

require_once __DIR__ . '/../../includes/functions.php';

class FunctionsTest extends TestCase {

    public function testFormatCurrency() {
        $this->assertEquals('$150.00', formatCurrency(150));
        $this->assertEquals('$1,250.50', formatCurrency(1250.5));
        $this->assertEquals('$0.00', formatCurrency(0));
        $this->assertEquals('$99.99', formatCurrency('99.99'));
    }

    public function testSafeString() {
        $this->assertEquals('Clean string', safeString('Clean string'));
        $this->assertEquals('Trimmed', safeString('   Trimmed   '));
        $this->assertEquals('', safeString(null));
        $this->assertEquals('&lt;b&gt;Bold&lt;/b&gt;', safeString('<b>Bold</b>'));
        $this->assertEquals('&#039;Single&#039; &amp; &quot;Double&quot;', safeString("'Single' & \"Double\""));
    }
}

