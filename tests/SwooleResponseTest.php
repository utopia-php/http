<?php

namespace Utopia\Http;

use PHPUnit\Framework\TestCase;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server as SwooleServer;
use Utopia\Http\Adapter\Swoole\Response;

/**
 * @requires extension swoole
 */
class SwooleResponseTest extends TestCase
{
    public function testSetSwooleServer(): void
    {
        $swooleResponse = $this->createMock(SwooleResponse::class);
        $swooleServer = $this->createMock(SwooleServer::class);

        $response = new Response($swooleResponse);
        $response->setSwooleServer($swooleServer);

        // Verify stream() doesn't fall back to parent (which would call write/end)
        // by checking that detach is called when server is set
        $swooleResponse->fd = 1;
        $swooleResponse->expects($this->once())->method('detach');
        $swooleServer->expects($this->atLeastOnce())->method('send')->willReturn(true);
        $swooleServer->expects($this->once())->method('close');

        $response->stream(
            function (int $offset, int $length) {
                return str_repeat('x', $length);
            },
            100
        );
    }

    public function testStreamFallsBackToParentWithoutServer(): void
    {
        $swooleResponse = $this->createMock(SwooleResponse::class);
        $response = new Response($swooleResponse);

        // Without setSwooleServer(), stream() should fall back to base impl
        // which uses write() and end() on the swoole response
        $swooleResponse->expects($this->atLeastOnce())->method('write')->willReturn(true);
        $swooleResponse->expects($this->once())->method('end');

        $data = 'Hello World';
        $response->stream(
            function (int $offset, int $length) use ($data) {
                return substr($data, $offset, $length);
            },
            strlen($data)
        );

        $this->assertTrue($response->isSent());
    }

    public function testStreamSendsRawHttpWithContentLength(): void
    {
        $swooleResponse = $this->createMock(SwooleResponse::class);
        $swooleServer = $this->createMock(SwooleServer::class);

        $response = new Response($swooleResponse);
        $response->setSwooleServer($swooleServer);
        $response->setContentType('application/octet-stream');

        $swooleResponse->fd = 42;
        $swooleResponse->expects($this->once())->method('detach');

        $sentData = [];
        $swooleServer->method('send')->willReturnCallback(
            function (int $fd, string $data) use (&$sentData) {
                $sentData[] = $data;
                return true;
            }
        );
        $swooleServer->expects($this->once())->method('close')->with(42);

        $body = str_repeat('A', 1000);
        $response->stream(
            function (int $offset, int $length) use ($body) {
                return substr($body, $offset, $length);
            },
            strlen($body)
        );

        // First send() call is the raw HTTP headers
        $rawHeaders = $sentData[0];
        $this->assertStringContainsString('HTTP/1.1 200 OK', $rawHeaders);
        $this->assertStringContainsString('Content-Length: 1000', $rawHeaders);
        $this->assertStringContainsString('Content-Type: application/octet-stream', $rawHeaders);
        $this->assertStringContainsString('Connection: close', $rawHeaders);
        $this->assertStringEndsWith("\r\n\r\n", $rawHeaders);

        // Second send() call is the body
        $this->assertSame($body, $sentData[1]);
    }

    public function testStreamSendsMultipleChunksForLargeBody(): void
    {
        $swooleResponse = $this->createMock(SwooleResponse::class);
        $swooleServer = $this->createMock(SwooleServer::class);

        $response = new Response($swooleResponse);
        $response->setSwooleServer($swooleServer);

        $swooleResponse->fd = 1;
        $swooleResponse->expects($this->once())->method('detach');

        $sendCount = 0;
        $swooleServer->method('send')->willReturnCallback(
            function () use (&$sendCount) {
                $sendCount++;
                return true;
            }
        );
        $swooleServer->method('close');

        // 5MB body = 3 chunks (2MB + 2MB + 1MB) + 1 header send = 4 sends
        $totalSize = 5 * 1024 * 1024;
        $response->stream(
            function (int $offset, int $length) {
                return str_repeat('B', $length);
            },
            $totalSize
        );

        // 1 header send + 3 body chunk sends
        $this->assertSame(4, $sendCount);
    }

    public function testStreamHandlesHeaderSendFailure(): void
    {
        $swooleResponse = $this->createMock(SwooleResponse::class);
        $swooleServer = $this->createMock(SwooleServer::class);

        $response = new Response($swooleResponse);
        $response->setSwooleServer($swooleServer);

        $swooleResponse->fd = 1;
        $swooleResponse->expects($this->once())->method('detach');

        // First send (headers) fails
        $swooleServer->expects($this->once())->method('send')->willReturn(false);
        $swooleServer->expects($this->once())->method('close')->with(1);

        $readerCalled = false;
        $response->stream(
            function (int $offset, int $length) use (&$readerCalled) {
                $readerCalled = true;
                return str_repeat('x', $length);
            },
            100
        );

        // Reader should never be called if header send fails
        $this->assertFalse($readerCalled);
    }

    public function testStreamHandlesBodySendFailure(): void
    {
        $swooleResponse = $this->createMock(SwooleResponse::class);
        $swooleServer = $this->createMock(SwooleServer::class);

        $response = new Response($swooleResponse);
        $response->setSwooleServer($swooleServer);

        $swooleResponse->fd = 1;
        $swooleResponse->expects($this->once())->method('detach');

        $sendCallCount = 0;
        $swooleServer->method('send')->willReturnCallback(
            function () use (&$sendCallCount) {
                $sendCallCount++;
                // First call (headers) succeeds, second call (first body chunk) fails
                return $sendCallCount <= 1;
            }
        );
        $swooleServer->method('close');

        $readerCalls = 0;
        $totalSize = 5 * 1024 * 1024; // Would be 3 chunks
        $response->stream(
            function (int $offset, int $length) use (&$readerCalls) {
                $readerCalls++;
                return str_repeat('x', $length);
            },
            $totalSize
        );

        // Only the first body chunk should be read before send failure breaks the loop
        $this->assertSame(1, $readerCalls);
    }

    public function testStreamIncludesCookiesInRawHttp(): void
    {
        $swooleResponse = $this->createMock(SwooleResponse::class);
        $swooleServer = $this->createMock(SwooleServer::class);

        $response = new Response($swooleResponse);
        $response->setSwooleServer($swooleServer);
        $response->addCookie('session', 'abc123', path: '/', secure: true, httponly: true, sameSite: 'Strict');

        $swooleResponse->fd = 1;
        $swooleResponse->expects($this->once())->method('detach');

        $sentHeaders = '';
        $swooleServer->method('send')->willReturnCallback(
            function (int $fd, string $data) use (&$sentHeaders) {
                if (empty($sentHeaders)) {
                    $sentHeaders = $data;
                }
                return true;
            }
        );
        $swooleServer->method('close');

        $response->stream(
            function (int $offset, int $length) {
                return str_repeat('x', $length);
            },
            100
        );

        $this->assertStringContainsString('Set-Cookie: session=abc123', $sentHeaders);
        $this->assertStringContainsString('Path=/', $sentHeaders);
        $this->assertStringContainsString('Secure', $sentHeaders);
        $this->assertStringContainsString('HttpOnly', $sentHeaders);
        $this->assertStringContainsString('SameSite=Strict', $sentHeaders);
    }

    public function testStreamWithDisabledPayload(): void
    {
        $swooleResponse = $this->createMock(SwooleResponse::class);
        $swooleServer = $this->createMock(SwooleServer::class);

        $response = new Response($swooleResponse);
        $response->setSwooleServer($swooleServer);
        $response->disablePayload();

        // With disabled payload, it should use normal Swoole API (not detach)
        $swooleResponse->expects($this->never())->method('detach');
        $swooleResponse->expects($this->once())->method('end');
        $swooleServer->expects($this->never())->method('send');

        $readerCalled = false;
        $response->stream(
            function (int $offset, int $length) use (&$readerCalled) {
                $readerCalled = true;
                return str_repeat('x', $length);
            },
            100
        );

        $this->assertFalse($readerCalled);
        $this->assertTrue($response->isSent());
    }

    public function testStreamDoesNotSendWhenAlreadySent(): void
    {
        $swooleResponse = $this->createMock(SwooleResponse::class);
        $swooleServer = $this->createMock(SwooleServer::class);

        $response = new Response($swooleResponse);
        $response->setSwooleServer($swooleServer);

        // Send response first
        $swooleResponse->method('end');
        @$response->send('already sent');

        // stream() should be a no-op
        $swooleResponse->expects($this->never())->method('detach');
        $swooleServer->expects($this->never())->method('send');

        $response->stream(
            function (int $offset, int $length) {
                return str_repeat('x', $length);
            },
            100
        );
    }

    public function testStreamSendsCorrectStatusCode(): void
    {
        $swooleResponse = $this->createMock(SwooleResponse::class);
        $swooleServer = $this->createMock(SwooleServer::class);

        $response = new Response($swooleResponse);
        $response->setSwooleServer($swooleServer);
        $response->setStatusCode(206); // Partial Content

        $swooleResponse->fd = 1;
        $swooleResponse->expects($this->once())->method('detach');

        $sentHeaders = '';
        $swooleServer->method('send')->willReturnCallback(
            function (int $fd, string $data) use (&$sentHeaders) {
                if (empty($sentHeaders)) {
                    $sentHeaders = $data;
                }
                return true;
            }
        );
        $swooleServer->method('close');

        $response->stream(
            function (int $offset, int $length) {
                return str_repeat('x', $length);
            },
            100
        );

        $this->assertStringContainsString('HTTP/1.1 206 Partial Content', $sentHeaders);
    }

    public function testStreamSendsArrayHeaders(): void
    {
        $swooleResponse = $this->createMock(SwooleResponse::class);
        $swooleServer = $this->createMock(SwooleServer::class);

        $response = new Response($swooleResponse);
        $response->setSwooleServer($swooleServer);
        $response->addHeader('X-Custom', 'value1', override: false);
        $response->addHeader('X-Custom', 'value2', override: false);

        $swooleResponse->fd = 1;
        $swooleResponse->expects($this->once())->method('detach');

        $sentHeaders = '';
        $swooleServer->method('send')->willReturnCallback(
            function (int $fd, string $data) use (&$sentHeaders) {
                if (empty($sentHeaders)) {
                    $sentHeaders = $data;
                }
                return true;
            }
        );
        $swooleServer->method('close');

        $response->stream(
            function (int $offset, int $length) {
                return str_repeat('x', $length);
            },
            100
        );

        $this->assertStringContainsString('X-Custom: value1', $sentHeaders);
        $this->assertStringContainsString('X-Custom: value2', $sentHeaders);
    }

    public function testBuildSetCookieHeader(): void
    {
        $swooleResponse = $this->createMock(SwooleResponse::class);
        $response = new Response($swooleResponse);

        // Use reflection to test the private method
        $method = new \ReflectionMethod($response, 'buildSetCookieHeader');
        $method->setAccessible(true);

        // Test basic cookie
        $result = $method->invoke($response, [
            'name' => 'test',
            'value' => 'value',
            'expire' => null,
            'path' => null,
            'domain' => null,
            'secure' => null,
            'httponly' => null,
            'samesite' => null,
        ]);
        $this->assertSame('test=value', $result);

        // Test cookie with all options
        $expire = time() + 3600;
        $result = $method->invoke($response, [
            'name' => 'session',
            'value' => 'abc123',
            'expire' => $expire,
            'path' => '/',
            'domain' => '.example.com',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $this->assertStringContainsString('session=abc123', $result);
        $this->assertStringContainsString('Expires=' . gmdate('D, d M Y H:i:s T', $expire), $result);
        $this->assertStringContainsString('Max-Age=', $result);
        $this->assertStringContainsString('Path=/', $result);
        $this->assertStringContainsString('Domain=.example.com', $result);
        $this->assertStringContainsString('Secure', $result);
        $this->assertStringContainsString('HttpOnly', $result);
        $this->assertStringContainsString('SameSite=Lax', $result);

        // Test cookie with special characters in value
        $result = $method->invoke($response, [
            'name' => 'data',
            'value' => 'hello world&foo=bar',
            'expire' => null,
            'path' => null,
            'domain' => null,
            'secure' => null,
            'httponly' => null,
            'samesite' => null,
        ]);
        $this->assertSame('data=' . urlencode('hello world&foo=bar'), $result);
    }
}
