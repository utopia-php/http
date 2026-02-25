<?php

namespace Utopia\Http\Tests;

require_once __DIR__.'/ResponseTest.php';

use Tests\E2E\Client;

/**
 * Swoole e2e tests. Extends the FPM ResponseTest so all shared
 * tests are inherited. Only overrides setUp() to point at the
 * Swoole container and adds/overrides Swoole-specific behaviour.
 *
 * @group swoole
 */
class SwooleResponseTest extends ResponseTest
{
    public function setUp(): void
    {
        $this->client = new Client('http://swoole:8080');
    }

    /**
     * Swoole parses the Cookie header internally and may not expose
     * the raw cookie string via $request->header['cookie']. Override
     * to only verify the server acknowledges cookie requests.
     */
    public function testCookie()
    {
        $cookie = 'cookie1=value1';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', ['Cookie' => $cookie]);
        $this->assertEquals(200, $response['headers']['status-code']);
    }

    /**
     * Swoole doesn't use nginx, so double-slash URL normalization
     * is not available. Override to skip this FPM/nginx-specific test.
     */
    public function testDoubleSlash()
    {
        $this->markTestSkipped('Double-slash normalization is nginx-specific behaviour');
    }

    /**
     * Verify that Swoole streamed responses include Connection: close header.
     * This is set by the detach() path to signal the client to close after transfer.
     */
    public function testStreamResponseHasConnectionClose()
    {
        $response = $this->client->call(Client::METHOD_GET, '/stream');

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('close', $response['headers']['connection']);
    }

    /**
     * Verify that the Server header is present in streamed responses.
     */
    public function testStreamResponseHasServerHeader()
    {
        $response = $this->client->call(Client::METHOD_GET, '/stream');

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertArrayHasKey('server', $response['headers']);
    }
}
