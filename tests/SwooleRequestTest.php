<?php

namespace Utopia\Http\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Http\Adapter\Swoole\Request;

class SwooleRequestTest extends TestCase
{
    private ?Request $request = null;

    public function setUp(): void
    {
        if (!class_exists(\Swoole\Http\Request::class)) {
            $this->markTestSkipped('The Swoole extension is required for this test.');
        }

        /** @var \Swoole\Http\Request $swooleRequest */
        $swooleRequest = new \Swoole\Http\Request();
        $swooleRequest->header = [];

        $this->request = new Request($swooleRequest);
    }

    public function tearDown(): void
    {
        $this->request = null;
    }

    public function testCanGetScalarHeaders(): void
    {
        $this->request?->getSwooleRequest()->header = [
            'x-replaced-path' => '/gateway',
        ];

        $this->assertEquals('/gateway', $this->request?->getHeader('x-replaced-path'));
    }

    public function testCanNormalizeArrayHeaders(): void
    {
        $this->request?->getSwooleRequest()->header = [
            'x-replaced-path' => ['/client', '/gateway'],
        ];

        $this->assertEquals('/gateway', $this->request?->getHeader('x-replaced-path'));
    }
}
