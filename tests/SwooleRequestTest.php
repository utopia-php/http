<?php

namespace Utopia\Http\Tests;

use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SwooleRequest;
use Utopia\Http\Adapter\Swoole\Request;

class SwooleRequestTest extends TestCase
{
    public function testCanGetCookieFromParsedSwooleCookies(): void
    {
        $swooleRequest = new SwooleRequest();
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
        $swooleRequest = new SwooleRequest();
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
