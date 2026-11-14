<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use VMS\Core\Request;
use VMS\Core\Response;

class RequestResponseTest extends TestCase
{
    public function testRequestResolvesPathAndStripsSubfolderAndQuery(): void
    {
        $request = new Request(
            ['page' => '2'],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI'    => '/vms/public/vehicles/15?page=2',
                'SCRIPT_NAME'    => '/vms/public/index.php',
            ]
        );

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/vehicles/15', $request->getPath());
        $this->assertTrue($request->isGet());
        $this->assertFalse($request->isPost());
        $this->assertSame('2', $request->input('page'));
        $this->assertSame('default', $request->input('missing', 'default'));
    }

    public function testRequestPostOverridesQueryInInput(): void
    {
        $request = new Request(
            ['name' => 'from_query'],
            ['name' => 'from_post', 'extra' => '1'],
            ['REQUEST_METHOD' => 'post', 'REQUEST_URI' => '/submit']
        );

        $this->assertSame('POST', $request->getMethod());
        $this->assertTrue($request->isPost());
        $this->assertSame('from_post', $request->input('name'));
        $this->assertSame(['name' => 'from_post', 'extra' => '1'], $request->all());
    }

    public function testRequestHeadersAndAjaxDetection(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD'       => 'POST',
            'REQUEST_URI'          => '/api/test',
            'HTTP_X_CSRF_TOKEN'    => 'abc123',
            'HTTP_X_REQUESTED_WITH'=> 'XMLHttpRequest',
            'CONTENT_TYPE'         => 'application/json',
        ]);

        $this->assertSame('abc123', $request->header('X-CSRF-Token'));
        $this->assertSame('application/json', $request->header('Content-Type'));
        $this->assertNull($request->header('X-Missing'));
        $this->assertTrue($request->isAjax());
        $this->assertSame('/api/test', $request->server('REQUEST_URI'));
        $this->assertSame('fallback', $request->server('MISSING_KEY', 'fallback'));
    }

    public function testResponseFactoriesAndXssSafeJson(): void
    {
        $html = Response::html('<h1>Hello</h1>', 201);
        $this->assertSame(201, $html->getStatusCode());
        $this->assertSame('<h1>Hello</h1>', $html->getContent());
        $this->assertSame('text/html; charset=utf-8', $html->getHeaders()['Content-Type']);

        $json = Response::json(['tag' => '<script>alert(1)</script>'], 200);
        $this->assertSame(200, $json->getStatusCode());
        $this->assertStringNotContainsString('<script>', $json->getContent());
        $this->assertSame('application/json; charset=utf-8', $json->getHeaders()['Content-Type']);

        $redirect = Response::redirect('/auth/login.php', 302)->setHeader('X-Custom', 'val');
        $this->assertSame(302, $redirect->getStatusCode());
        $this->assertSame('/auth/login.php', $redirect->getHeaders()['Location']);
        $this->assertSame('val', $redirect->getHeaders()['X-Custom']);
    }
}
