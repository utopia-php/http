<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Tests\E2E\Client;

class ResponseTest extends TestCase
{
    protected Client $client;

    public function setUp(): void
    {
        $this->client = new Client();
    }

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

    public function testFile()
    {
        $response = $this->client->call(Client::METHOD_GET, '/humans.txt');
        $this->assertEquals(204, $response['headers']['status-code']);
    }

    public function testEarlyResponse()
    {
        // Ensure response from action is not recieved
        $response = $this->client->call(Client::METHOD_GET, '/early-response');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertNotEquals('Action response', $response['body']);

        // 2nd request would catch if action from first ran
        $response = $this->client->call(Client::METHOD_GET, '/early-response');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('Init response. Actioned before: no', $response['body']);
    }
}
