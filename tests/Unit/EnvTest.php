<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use VMS\Core\Env;

class EnvTest extends TestCase
{
    private string $tempEnvFile;

    protected function setUp(): void
    {
        $this->tempEnvFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vms_test_' . bin2hex(random_bytes(4)) . '.env';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempEnvFile)) {
            @unlink($this->tempEnvFile);
        }
        unset(
            $_ENV['VMS_TEST_STR'],
            $_ENV['VMS_TEST_QUOTED'],
            $_ENV['VMS_TEST_BOOL_T'],
            $_ENV['VMS_TEST_BOOL_F'],
            $_ENV['VMS_TEST_NULL'],
            $_ENV['VMS_TEST_EMPTY'],
            $_SERVER['VMS_TEST_STR'],
            $_SERVER['VMS_TEST_QUOTED'],
            $_SERVER['VMS_TEST_BOOL_T'],
            $_SERVER['VMS_TEST_BOOL_F'],
            $_SERVER['VMS_TEST_NULL'],
            $_SERVER['VMS_TEST_EMPTY']
        );
    }

    public function testLoadReturnsFalseForMissingFile(): void
    {
        $this->assertFalse(Env::load($this->tempEnvFile . '.nonexistent'));
    }

    public function testLoadParsesCommentsQuotesAndTypedValues(): void
    {
        $content = <<<ENV
# Comment line
VMS_TEST_STR=hello_world
VMS_TEST_QUOTED="quoted value"
VMS_TEST_BOOL_T=true
VMS_TEST_BOOL_F=(false)
VMS_TEST_NULL=null
VMS_TEST_EMPTY=empty
INVALID_LINE_WITHOUT_EQUALS
ENV;
        file_put_contents($this->tempEnvFile, $content);

        $this->assertTrue(Env::load($this->tempEnvFile));
        $this->assertSame('hello_world', Env::get('VMS_TEST_STR'));
        $this->assertSame('quoted value', Env::get('VMS_TEST_QUOTED'));
        $this->assertTrue(Env::get('VMS_TEST_BOOL_T'));
        $this->assertFalse(Env::get('VMS_TEST_BOOL_F'));
        $this->assertNull(Env::get('VMS_TEST_NULL'));
        $this->assertSame('', Env::get('VMS_TEST_EMPTY'));
        $this->assertSame('fallback', Env::get('VMS_TEST_UNDEFINED_KEY', 'fallback'));
    }
}
