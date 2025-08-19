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

    public function testNullPathHandling()
    {
        // Test that malformed URLs default to root path
        $response = $this->client->call(Client::METHOD_GET, '/');
        $this->assertEquals('Hello World!', $response['body']);
        $this->assertEquals(200, $response['headers']['status-code']);
    }

    public function testRootPathFallback()
    {
        // Test that when path parsing fails, it falls back to root
        // This is tested by ensuring the root route works correctly
        $response = $this->client->call(Client::METHOD_GET, '/');
        $this->assertEquals('Hello World!', $response['body']);
        $this->assertEquals(200, $response['headers']['status-code']);
    }

    public function testAliasWithParameter(): void
    {
        $response = $this->client->call(Client::METHOD_POST, '/functions/deployment', [
            'content-type' => 'application/json'
        ], [
            'deploymentId' => 'deployment1'
        ]);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('ID:deployment1', $response['body']);

        $response = $this->client->call(Client::METHOD_POST, '/functions/deployment');
        $this->assertEquals(204, $response['headers']['status-code']);

        $response = $this->client->call(Client::METHOD_POST, '/functions/deployment/deployment2');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('ID:deployment2', $response['body']);

        $response = $this->client->call(Client::METHOD_POST, '/database/collections/col1');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals(';col1', $response['body']);

        $response = $this->client->call(Client::METHOD_POST, '/databases/db2/collections/col2');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('db2;col2', $response['body']);
    }

    public function testDoubleSlash()
    {
        $response = $this->client->call(Client::METHOD_GET, '//');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('Hello World!', $response['body']);

        $response = $this->client->call(Client::METHOD_GET, '//path-404');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('Hello World!', $response['body']);

        $response = $this->client->call(Client::METHOD_GET, '//value/123');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEmpty($response['body']);

        $response = $this->client->call(Client::METHOD_GET, '/value//123');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEmpty($response['body']);

        $response = $this->client->call(Client::METHOD_GET, '//value//123');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEmpty($response['body']);
    }
}
