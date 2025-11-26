<?php

namespace Utopia\Http\Tests;

use PHPUnit\Framework\TestCase;
use Tests\E2E\Client;

/**
 * @group fpm
 * @group e2e
 */
class ResponseFPMTest extends TestCase
{
    use BaseTest;
    protected Client $client;

    public function setUp(): void
    {
        $this->client = new Client('http://fpm');
    }

    /**
     * Override cookie test for FPM specific behavior
     * FPM preserves original cookie format while Swoole normalizes it
     */
    public function testCookie()
    {
        // One cookie
        $cookie = 'cookie1=value1';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', [ 'Cookie' => $cookie ]);
        $this->assertSame(200, $response['headers']['status-code']);
        $this->assertSame($cookie, $response['body']);

        // Two cookies with space (FPM preserves original format)
        $cookie = 'cookie1=value1; cookie2=value2';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', [ 'Cookie' => $cookie ]);
        $this->assertSame(200, $response['headers']['status-code']);
        $this->assertSame($cookie, $response['body']);

        // Two cookies without space (FPM preserves original format)
        $cookie = 'cookie1=value1;cookie2=value2';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', [ 'Cookie' => $cookie ]);
        $this->assertSame(200, $response['headers']['status-code']);
        $this->assertSame($cookie, $response['body']);

        // Cookie with "=" in value
        $cookie = 'cookie1=value1=value2';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', [ 'Cookie' => $cookie ]);
        $this->assertSame(200, $response['headers']['status-code']);
        $this->assertSame($cookie, $response['body']);

        // Case sensitivity for cookie names
        $cookie = 'cookie1=v1; Cookie1=v2';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', [ 'Cookie' => $cookie ]);
        $this->assertSame(200, $response['headers']['status-code']);
        $this->assertSame($cookie, $response['body']);
    }
}
