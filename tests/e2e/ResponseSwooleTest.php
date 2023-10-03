<?php

namespace Utopia\Http\Tests;

use PHPUnit\Framework\TestCase;
use Tests\E2E\Client;

class ResponseSwooleTest extends TestCase
{
    use BaseTest;
    protected Client $client;

    public function setUp(): void
    {
        $this->client = new Client('http://swoole');
    }

    public function testSwooleResources(): void
    {
        $response = $this->client->call(Client::METHOD_DELETE, '/swoole-test');
        $this->assertEquals('DELETE', $response['body']);
    }
}
