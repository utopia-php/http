<?php

namespace Utopia\Http\Tests;

use Tests\E2E\Client;

trait BaseTest
{
    public function testResponse(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/');
        $this->assertSame('Hello World!', $response['body']);
    }

    public function testResponseValue(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/value/123');
        $this->assertSame('123', $response['body']);
    }

    public function testChunkResponse(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/chunked');
        $this->assertSame('Hello World!', $response['body']);
    }

    public function testRedirect(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/redirect');
        $this->assertSame('Hello World!', $response['body']);
    }

    public function testFile(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/humans.txt');
        $this->assertSame(204, $response['headers']['status-code']);
    }

    public function testSetCookie(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/set-cookie');
        $this->assertSame(200, $response['headers']['status-code']);
        $this->assertSame('value1', $response['cookies']['key1']);
        $this->assertSame('value2', $response['cookies']['key2']);
    }

    public function testAliases(): void
    {
        $paths = ['/aliased', '/aliased-1', '/aliased-2', '/aliased-3'];

        foreach ($paths as $path) {
            $response = $this->client->call(Client::METHOD_GET, $path);
            $this->assertSame(200, $response['headers']['status-code']);
            $this->assertSame('Aliased!', $response['body']);
        }
    }
}
