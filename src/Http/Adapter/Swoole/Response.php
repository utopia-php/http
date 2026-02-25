<?php

namespace Utopia\Http\Adapter\Swoole;

use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server as SwooleServer;
use Utopia\Http\Response as UtopiaResponse;

class Response extends UtopiaResponse
{
    /**
     * Swoole Response Object
     *
     * @var SwooleResponse
     */
    protected SwooleResponse $swoole;

    /**
     * Swoole HTTP Server for raw TCP sends after detach().
     */
    protected ?SwooleServer $server = null;

    /**
     * Response constructor.
     */
    public function __construct(SwooleResponse $response)
    {
        $this->swoole = $response;
        parent::__construct(\microtime(true));
    }

    /**
     * Set the Swoole HTTP Server instance.
     *
     * Required for stream() to use detach() + server->send() for
     * sending responses with Content-Length and streaming body.
     */
    public function setSwooleServer(SwooleServer $server): void
    {
        $this->server = $server;
    }

    /**
     * Write
     *
     * @param  string  $content
     * @return bool False if write cannot complete, such as request ended by client
     */
    public function write(string $content): bool
    {
        return $this->swoole->write($content);
    }

    /**
     * End
     *
     * @param  string|null  $content
     * @return void
     */
    public function end(?string $content = null): void
    {
        $this->swoole->end($content);
    }

    /**
     * Stream a large response body with Content-Length.
     *
     * Overrides the base implementation to use Swoole's detach() +
     * $server->send() pattern. This bypasses Swoole's forced chunked
     * Transfer-Encoding, allowing Content-Length to be sent with a
     * streaming body so browsers can show download progress.
     *
     * @param callable(int, int): string $reader fn($offset, $length) returns chunk data
     * @param int $totalSize Total response body size in bytes
     */
    public function stream(callable $reader, int $totalSize): void
    {
        if ($this->sent) {
            return;
        }

        // Fallback to base implementation if server not available
        if ($this->server === null) {
            parent::stream($reader, $totalSize);
            return;
        }

        $this->sent = true;

        if ($this->disablePayload) {
            $this->appendCookies()->appendHeaders();
            $this->end();
            return;
        }

        // Build raw HTTP response with Content-Length
        $this->addHeader('Content-Length', (string) $totalSize, override: true);
        $this->addHeader('Connection', 'close', override: true);
        $this->addHeader('X-Debug-Speed', (string) (\microtime(true) - $this->startTime), override: true);

        $serverHeader = $this->headers['Server'] ?? 'Utopia/Http';
        $this->addHeader('Server', $serverHeader, override: true);

        if (!empty($this->contentType)) {
            $this->addHeader('Content-Type', $this->contentType, override: true);
        }

        $statusCode = $this->getStatusCode();
        $reason = $this->statusCodes[$statusCode] ?? 'Unknown';
        $raw = "HTTP/1.1 {$statusCode} {$reason}\r\n";

        foreach ($this->headers as $key => $value) {
            if (\is_array($value)) {
                foreach ($value as $v) {
                    $raw .= "{$key}: {$v}\r\n";
                }
            } else {
                $raw .= "{$key}: {$value}\r\n";
            }
        }

        foreach ($this->cookies as $cookie) {
            $raw .= 'Set-Cookie: ' . $this->buildSetCookieHeader($cookie) . "\r\n";
        }

        $raw .= "\r\n";

        // Detach from Swoole's HTTP layer and send raw TCP
        $fd = $this->swoole->fd;
        $this->swoole->detach();

        if ($this->server->send($fd, $raw) === false) {
            $this->server->close($fd);
            $this->disablePayload();
            return;
        }

        // Stream body in 2MB chunks
        $chunkSize = 2 * 1024 * 1024;
        for ($offset = 0; $offset < $totalSize; $offset += $chunkSize) {
            $length = \min($chunkSize, $totalSize - $offset);
            $data = $reader($offset, $length);
            if ($this->server->send($fd, $data) === false) {
                break;
            }
            unset($data);
        }

        $this->server->close($fd);
        $this->disablePayload();
    }

    /**
     * Build a Set-Cookie header string from a cookie array.
     */
    private function buildSetCookieHeader(array $cookie): string
    {
        $parts = [\urlencode($cookie['name']) . '=' . \urlencode($cookie['value'] ?? '')];

        if (!empty($cookie['expire'])) {
            $parts[] = 'Expires=' . \gmdate('D, d M Y H:i:s T', $cookie['expire']);
            $parts[] = 'Max-Age=' . \max(0, $cookie['expire'] - \time());
        }
        if (!empty($cookie['path'])) {
            $parts[] = 'Path=' . $cookie['path'];
        }
        if (!empty($cookie['domain'])) {
            $parts[] = 'Domain=' . $cookie['domain'];
        }
        if (!empty($cookie['secure'])) {
            $parts[] = 'Secure';
        }
        if (!empty($cookie['httponly'])) {
            $parts[] = 'HttpOnly';
        }
        if (!empty($cookie['samesite'])) {
            $parts[] = 'SameSite=' . $cookie['samesite'];
        }

        return \implode('; ', $parts);
    }

    /**
     * Get status code reason
     *
     * Get HTTP response status code reason from available options. If status code is unknown an exception will be thrown.
     *
     * @param  int  $code
     * @return string
     *
     * @throws \Exception
     */
    protected function getStatusCodeReason(int $code): string
    {
        if (!\array_key_exists($code, $this->statusCodes)) {
            throw new \Exception('Unknown HTTP status code');
        }

        return $this->statusCodes[$code];
    }

    /**
     * Send Status Code
     *
     * @param  int  $statusCode
     * @return void
     */
    protected function sendStatus(int $statusCode): void
    {
        $this->swoole->status((string) $statusCode, $this->getStatusCodeReason($statusCode));
    }

    /**
     * Send Header
     *
     * @param  string  $key
     * @param  string|array<string>  $value
     * @return void
     */
    public function sendHeader(string $key, mixed $value): void
    {
        $this->swoole->header($key, $value);
    }

    /**
     * Send Cookie
     *
     * Send a cookie
     *
     * @param  string  $name
     * @param  string  $value
     * @param  array<string, mixed>  $options
     * @return void
     */
    protected function sendCookie(string $name, string $value, array $options): void
    {
        $this->swoole->cookie(
            $name,
            value: $value,
            expires: $options['expire'] ?? 0,
            path: $options['path'] ?? '',
            domain: $options['domain'] ?? '',
            secure: $options['secure'] ?? false,
            httponly: $options['httponly'] ?? false,
            samesite: $options['samesite'] ?? false,
        );
    }
}
