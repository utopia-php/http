<?php

declare(strict_types=1);

namespace Utopia\Http\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Utopia\Http\Adapter\FPM\RequestFactory;

final class RequestTest extends TestCase
{
    public function setUp(): void
    {
        foreach (array_keys($_SERVER) as $key) {
            if (str_starts_with($key, 'HTTP_')) {
                unset($_SERVER[$key]);
            }
        }

        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_SCHEME'], $_SERVER['CONTENT_TYPE']);
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];
    }

    public function testFpmRequestIsPsrServerRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users?active=1';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_GET = ['active' => '1'];
        $_COOKIE = ['session' => 'abc'];

        $request = new RequestFactory()->create();

        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://example.com/users?active=1', (string) $request->getUri());
        $this->assertSame(['active' => '1'], $request->getQueryParams());
        $this->assertSame(['session' => 'abc'], $request->getCookieParams());
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
    }

    public function testPsrMutatorsAreImmutable(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $request = new RequestFactory()->create();
        $changed = $request
            ->withMethod('POST')
            ->withQueryParams(['key' => 'value'])
            ->withHeader('X-Test', 'yes');

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('POST', $changed->getMethod());
        $this->assertSame([], $request->getQueryParams());
        $this->assertSame(['key' => 'value'], $changed->getQueryParams());
        $this->assertFalse($request->hasHeader('X-Test'));
        $this->assertSame('yes', $changed->getHeaderLine('X-Test'));
    }
}
