<?php

namespace Utopia\Http\Tests;

require_once __DIR__ . '/ResponseTest.php';

use Tests\E2E\Client;

class SwooleResponseTest extends ResponseTest
{
    public static function setUpBeforeClass(): void
    {
        $host = getenv('SWOOLE_HOST') ?: 'swoole-web';
        $sock = @fsockopen($host, 80, $errno, $errstr, 2);
        if (!$sock) {
            self::markTestSkipped('Swoole server not available at ' . $host . ':80');
        }
        fclose($sock);
    }

    public function setUp(): void
    {
        $host = getenv('SWOOLE_HOST') ?: 'swoole-web';
        $this->client = new Client('http://' . $host);
    }

    // ── Detach mode (default): preserves Content-Length ──

    public function testDetachStreamGeneratorHasContentLength(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/stream/generator');

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('chunk1-chunk2-chunk3', $response['body']);
        $this->assertArrayHasKey('content-length', $response['headers']);
        $this->assertEquals('20', $response['headers']['content-length']);
    }

    public function testDetachStreamCallableHasContentLength(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/stream/callable');

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals(str_repeat('A', 1000), $response['body']);
        $this->assertArrayHasKey('content-length', $response['headers']);
        $this->assertEquals('1000', $response['headers']['content-length']);
    }

    public function testDetachStreamGeneratorLargeDataHasContentLength(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/stream/generator-large');

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals(500000, strlen($response['body']));
        $this->assertEquals('500000', $response['headers']['content-length']);
        $this->assertEquals(str_repeat('A', 100000), substr($response['body'], 0, 100000));
        $this->assertEquals(str_repeat('E', 100000), substr($response['body'], 400000, 100000));
    }

    // ── Non-detach mode: chunked Transfer-Encoding, no Content-Length ──

    public function testNonDetachStreamGenerator(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/stream/non-detach/generator');

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('nd-chunk1-nd-chunk2-nd-chunk3', $response['body']);
        $this->assertArrayNotHasKey('content-length', $response['headers']);
    }

    /**
     * Override: Swoole parses cookies internally and the reconstructed Cookie header
     * always uses '; ' separator, so 'cookie1=value1;cookie2=value2' becomes
     * 'cookie1=value1; cookie2=value2'. We test the normalized format here.
     */
    public function testCookie()
    {
        // One cookie
        $cookie = 'cookie1=value1';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', ['Cookie' => $cookie]);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals($cookie, $response['body']);

        // Two cookies
        $cookie = 'cookie1=value1; cookie2=value2';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', ['Cookie' => $cookie]);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals($cookie, $response['body']);

        // Two cookies without optional space (Swoole normalizes to '; ')
        $response = $this->client->call(Client::METHOD_GET, '/cookies', ['Cookie' => 'cookie1=value1;cookie2=value2']);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('cookie1=value1; cookie2=value2', $response['body']);

        // Cookie with "=" in value
        $cookie = 'cookie1=value1=value2';
        $response = $this->client->call(Client::METHOD_GET, '/cookies', ['Cookie' => $cookie]);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals($cookie, $response['body']);

        // Case sensitivity for cookie names (Swoole lowercases keys)
        $response = $this->client->call(Client::METHOD_GET, '/cookies', ['Cookie' => 'cookie1=v1; Cookie1=v2']);
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertNotEmpty($response['body']);
    }

    public function testNonDetachStreamCallable(): void
    {
        $response = $this->client->call(Client::METHOD_GET, '/stream/non-detach/callable');

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals(str_repeat('B', 1000), $response['body']);
        $this->assertArrayNotHasKey('content-length', $response['headers']);
    }
}
