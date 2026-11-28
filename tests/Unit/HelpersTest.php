<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use VMS\Core\Session;

class HelpersTest extends TestCase
{
    public function testHtmlEscapeHelper(): void
    {
        $this->assertSame('&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;', e("<script>alert('x')</script>"));
        $this->assertSame('', e(null));
        $this->assertSame('42', e(42));
    }

    public function testOldInputHelper(): void
    {
        Session::start();
        $_SESSION['_vms_old_inputs'] = ['email' => 'owner@example.com'];

        $this->assertSame('owner@example.com', old('email'));
        $this->assertSame('fallback', old('missing_field', 'fallback'));

        unset($_SESSION['_vms_old_inputs']);
    }

    public function testFlashAndCsrfHelpers(): void
    {
        Session::start();

        $token = csrf_token();
        $this->assertSame(64, strlen($token));
        $this->assertStringContainsString('name="csrf_token"', csrf_field());
        $this->assertStringContainsString($token, csrf_field());

        flash('notice', 'Saved!');
        $this->assertSame('Saved!', flash('notice'));
        $this->assertNull(flash('notice'));
    }
}
