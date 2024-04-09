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
}
