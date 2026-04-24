<?php

namespace Utopia\Http\Tests;

use Tests\E2E\Client;

trait BaseTest
{
    public function testResponse(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/');
        $this->assertEquals('Hello World!', $response['body']);
    }

    public function testResponseValue(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/value/123');
        $this->assertEquals('123', $response['body']);
    }

    public function testChunkResponse(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/chunked');
        $this->assertEquals('Hello World!', $response['body']);
    }

    public function testRedirect(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/redirect');
        $this->assertEquals('Hello World!', $response['body']);
    }

    public function testFile(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/humans.txt');
        $this->assertEquals(204, $response['headers']['status-code']);
    }

    public function testSetCookie(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/set-cookie');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('value1', $response['cookies']['key1']);
        $this->assertEquals('value2', $response['cookies']['key2']);
    }

    public function testAliases(): void
    {
        $paths = ['/aliased', '/aliased-1', '/aliased-2', '/aliased-3'];

        foreach ($paths as $path) {
            $response = $this->client->call(Client::METHOD_GET, $path);
            $this->assertEquals(200, $response['headers']['status-code']);
            $this->assertEquals('Aliased!', $response['body']);
        }
    }
}
