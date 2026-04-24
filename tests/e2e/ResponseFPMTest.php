<?php

declare(strict_types=1);

namespace Utopia\Http\Tests;

use PHPUnit\Framework\TestCase;
use Tests\E2E\Client;

class ResponseFPMTest extends TestCase
{
    use BaseTest;
    protected Client $client;

    public function setUp(): void
    {
        $this->client = new Client();
    }

    public function testCookie(): void
    {
        $cookie = 'cookie1=value1';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', ['Cookie: ' . $cookie]);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals($cookie, $response['body']);

        $cookie = 'cookie1=value1; cookie2=value2';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', ['Cookie: ' . $cookie]);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals($cookie, $response['body']);

        $cookie = 'cookie1=value1;cookie2=value2';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', ['Cookie: ' . $cookie]);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals($cookie, $response['body']);

        $cookie = 'cookie1=value1=value2';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', ['Cookie: ' . $cookie]);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals($cookie, $response['body']);

        $cookie = 'cookie1=v1; Cookie1=v2';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', ['Cookie: ' . $cookie]);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals($cookie, $response['body']);
    }
}
