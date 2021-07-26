<?php

namespace Utopia\Tests;

use Tests\E2E\Client;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function setUp(): void
    {
        $this->client = new Client();
    }

    public function tearDown(): void
    {
        $this->client = null;
    }
    
    /**
     * @var Client $client
     */
    protected $client;

    public function testResponse()
    {
        $response = $this->client->call(Client::METHOD_GET, '/');
        
        $this->assertEquals('Hello World!', $response['body']);

    }

    public function testChunkResponse()
    {
        $response = $this->client->call(Client::METHOD_GET, '/chunked');
        
        $this->assertEquals('Hello World!', $response['body']);

    }
}