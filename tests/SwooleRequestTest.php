<?php

namespace Utopia\Http\Tests;

use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SwooleRequest;
use Utopia\Http\Adapter\Swoole\Request;

class SwooleRequestTest extends TestCase
{
    private function createSwooleRequest(): SwooleRequest
    {
        if (!\class_exists(SwooleRequest::class)) {
            $this->markTestSkipped('Swoole extension is not available.');
        }

        return new SwooleRequest();
    }

    public function testCanGetCookieFromParsedSwooleCookies(): void
    {
        $swooleRequest = $this->createSwooleRequest();
        $swooleRequest->cookie = [
            'cookie1' => 'value1',
            'cookie2' => 'value2',
        ];

        $request = new Request($swooleRequest);

        $this->assertSame('value1', $request->getCookie('cookie1', ''));
        $this->assertSame('value2', $request->getCookie('cookie2', ''));
        $this->assertSame('fallback', $request->getCookie('missing', 'fallback'));
    }

    public function testGetHeadersDoesNotSynthesizeCookieHeader(): void
    {
        $swooleRequest = $this->createSwooleRequest();
        $swooleRequest->header = [
            'host' => 'localhost',
        ];
        $swooleRequest->cookie = [
            'cookie1' => 'value1',
        ];

        $request = new Request($swooleRequest);

        $this->assertSame(['host' => 'localhost'], $request->getHeaders());
    }
}
