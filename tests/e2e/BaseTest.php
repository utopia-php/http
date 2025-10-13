<?php

namespace Utopia\Http\Tests;

use Tests\E2E\Client;

trait BaseTest
{
    public function testResponse()
    {
        $response = $this->client->call(Client::METHOD_GET, '/');
        $this->assertEquals('Hello World!', $response['body']);
    }

    public function testResponseValue()
    {
        $response = $this->client->call(Client::METHOD_GET, '/value/123');
        $this->assertEquals('123', $response['body']);
    }

    public function testHeaders()
    {
        $response = $this->client->call(Client::METHOD_GET, '/headers');
        $this->assertGreaterThan(8, count($response['headers']));
        $this->assertEquals('value1', $response['headers']['key1']);
        $this->assertEquals('value2', $response['headers']['key2']);
        $this->assertNotEmpty($response['body']);
    }

    public function testHead()
    {
        $response = $this->client->call(Client::METHOD_HEAD, '/headers');
        $this->assertGreaterThan(8, $response['headers']);
        $this->assertEquals('value1', $response['headers']['key1']);
        $this->assertEquals('value2', $response['headers']['key2']);
        $this->assertEmpty(trim($response['body']));
    }

    public function testNoContent()
    {
        $response = $this->client->call(Client::METHOD_DELETE, '/no-content');
        $this->assertEquals(204, $response['headers']['status-code']);
        $this->assertEmpty(trim($response['body']));
    }

    public function testChunkResponse()
    {
        $response = $this->client->call(Client::METHOD_GET, '/chunked');
        $this->assertEquals('Hello World!', $response['body']);
    }

    public function testRedirect()
    {
        $response = $this->client->call(Client::METHOD_GET, '/redirect');
        $this->assertEquals('Hello World!', $response['body']);
    }

    public function testHumans()
    {
        $response = $this->client->call(Client::METHOD_GET, '/humans.txt');
        $this->assertEquals('humans.txt', $response['body']);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('Utopia', $response['headers']['x-engine']);
    }

    public function testParamInjection()
    {
        $response = $this->client->call(Client::METHOD_GET, '/param-injection?param=1234567891011');
        $this->assertEquals(400, $response['headers']['status-code']);
        $this->assertStringStartsWith('Invalid `param` param: Value must be a valid string and at least 1 chars and no longer than 10 chars', $response['body']);

        $response = $this->client->call(Client::METHOD_GET, '/param-injection?param=test4573');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertStringStartsWith('Hello World!test4573', $response['body']);
    }

    public function testNotFound()
    {
        $response = $this->client->call(Client::METHOD_GET, '/non-existing-page');
        $this->assertEquals(404, $response['headers']['status-code']);
        $this->assertStringStartsWith('Not Found on ', $response['body']);
    }

    public function testCookie()
    {
        // One cookie
        $cookie = 'cookie1=value1';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', [ 'Cookie' => $cookie ]);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals($cookie, $response['body']);

        // Two cookiees
        $cookie = 'cookie1=value1; cookie2=value2';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', [ 'Cookie' => $cookie ]);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals($cookie, $response['body']);

        // Two cookies without optional space
        $cookie = 'cookie1=value1;cookie2=value2';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', [ 'Cookie' => $cookie ]);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals($cookie, $response['body']);

        // Cookie with "=" in value
        $cookie = 'cookie1=value1=value2';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', [ 'Cookie' => $cookie ]);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals($cookie, $response['body']);

        // Case sensitivity for cookie names
        $cookie = 'cookie1=v1; Cookie1=v2';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', [ 'Cookie' => $cookie ]);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals($cookie, $response['body']);
    }

    public function testSetCookie()
    {
        $response = $this->client->call(Client::METHOD_GET, '/set-cookie');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('value1', $response['cookies']['key1']);
        $this->assertEquals('value2', $response['cookies']['key2']);
    }
}
