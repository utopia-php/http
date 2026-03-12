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
     * Swoole Server Object (needed for raw TCP streaming with Content-Length)
     *
     * @var SwooleServer|null
     */
    protected ?SwooleServer $server = null;

    /**
     * Whether to use detach() + raw TCP send for streaming.
     * When true (and server is set): preserves Content-Length header so browsers show download progress.
     * When false: uses Swoole's write() which forces chunked Transfer-Encoding.
     *
     * @var bool
     */
    protected bool $detach = true;

    /**
     * Response constructor.
     */
    public function __construct(SwooleResponse $response)
    {
        $this->swoole = $response;
        parent::__construct(\microtime(true));
    }

    /**
     * Set the Swoole server instance for raw TCP streaming.
     *
     * @param  SwooleServer  $server
     * @return static
     */
    public function setServer(SwooleServer $server): static
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Set whether to detach from Swoole's HTTP layer for streaming.
     *
     * When enabled (default): uses detach() + $server->send() to preserve
     * Content-Length so browsers show download progress bars.
     *
     * When disabled: uses Swoole's write() which applies chunked Transfer-Encoding.
     *
     * @param  bool  $detach
     * @return static
     */
    public function setDetach(bool $detach): static
    {
        $this->detach = $detach;

        return $this;
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
     * Stream response
     *
     * Uses detach() + $server->send() for raw TCP streaming that preserves
     * Content-Length (so browsers show download progress). Falls back to
     * the base class implementation if no server instance is available.
     *
     * @param  callable|\Generator  $source  Either a callable($offset, $length) or a Generator yielding string chunks
     * @param  int  $totalSize  Total size of the content in bytes
     * @return void
     */
    public function stream(callable|\Generator $source, int $totalSize): void
    {
        if ($this->sent) {
            return;
        }

        $this->sent = true;

        if ($this->disablePayload) {
            $this->appendCookies();
            $this->appendHeaders();
            $this->swoole->end();
            $this->disablePayload();

            return;
        }

        // When detach is enabled and server is available, use raw TCP streaming
        // to preserve Content-Length (browsers show download progress).
        if ($this->detach && $this->server !== null) {
            $this->streamDetached($source, $totalSize);

            return;
        }

        // Non-detach path: use Swoole's write() (chunked Transfer-Encoding)
        $this->addHeader('X-Debug-Speed', (string) (microtime(true) - $this->startTime), override: true);

        if (!empty($this->contentType)) {
            $this->addHeader('Content-Type', $this->contentType, override: true);
        }

        $this->appendCookies();
        $this->appendHeaders();
        $this->sendStatus($this->statusCode);

        if ($source instanceof \Generator) {
            foreach ($source as $chunk) {
                if (!empty($chunk)) {
                    $this->size += strlen($chunk);
                    if ($this->swoole->write($chunk) === false) {
                        break;
                    }
                }
            }
        } else {
            $length = self::CHUNK_SIZE;
            for ($offset = 0; $offset < $totalSize; $offset += $length) {
                $chunk = $source($offset, min($length, $totalSize - $offset));
                if (!empty($chunk)) {
                    $this->size += strlen($chunk);
                    if ($this->swoole->write($chunk) === false) {
                        break;
                    }
                }
            }
        }

        $this->swoole->end();
        $this->disablePayload();
    }

    /**
     * Stream using detach() + raw TCP send to preserve Content-Length.
     *
     * @param  callable|\Generator  $source
     * @param  int  $totalSize
     * @return void
     */
    protected function streamDetached(callable|\Generator $source, int $totalSize): void
    {
        $this->addHeader('Content-Length', (string) $totalSize, override: true);
        $this->addHeader('Connection', 'close', override: true);
        $this->addHeader('X-Debug-Speed', (string) (microtime(true) - $this->startTime), override: true);

        if (!empty($this->contentType)) {
            $this->addHeader('Content-Type', $this->contentType, override: true);
        }

        $statusReason = $this->getStatusCodeReason($this->statusCode);
        $rawHeaders = "HTTP/1.1 {$this->statusCode} {$statusReason}\r\n";

        foreach ($this->headers as $key => $value) {
            if (\is_array($value)) {
                foreach ($value as $v) {
                    $rawHeaders .= "{$key}: {$v}\r\n";
                }
            } else {
                $rawHeaders .= "{$key}: {$value}\r\n";
            }
        }

        foreach ($this->cookies as $cookie) {
            $cookieStr = \urlencode($cookie['name']) . '=' . \urlencode($cookie['value'] ?? '');
            if (!empty($cookie['expire'])) {
                $cookieStr .= '; Expires=' . \gmdate('D, d M Y H:i:s T', $cookie['expire']);
            }
            if (!empty($cookie['path'])) {
                $cookieStr .= '; Path=' . $cookie['path'];
            }
            if (!empty($cookie['domain'])) {
                $cookieStr .= '; Domain=' . $cookie['domain'];
            }
            if (!empty($cookie['secure'])) {
                $cookieStr .= '; Secure';
            }
            if (!empty($cookie['httponly'])) {
                $cookieStr .= '; HttpOnly';
            }
            if (!empty($cookie['samesite'])) {
                $cookieStr .= '; SameSite=' . $cookie['samesite'];
            }
            $rawHeaders .= "Set-Cookie: {$cookieStr}\r\n";
        }

        $rawHeaders .= "\r\n";

        $fd = $this->swoole->fd;
        $this->swoole->detach();

        if ($this->server->send($fd, $rawHeaders) === false) {
            $this->disablePayload();

            return;
        }

        if ($source instanceof \Generator) {
            foreach ($source as $chunk) {
                if (!empty($chunk)) {
                    $this->size += strlen($chunk);
                    if ($this->server->send($fd, $chunk) === false) {
                        break;
                    }
                }
            }
        } else {
            $length = self::CHUNK_SIZE;
            for ($offset = 0; $offset < $totalSize; $offset += $length) {
                $chunk = $source($offset, min($length, $totalSize - $offset));
                if (!empty($chunk)) {
                    $this->size += strlen($chunk);
                    if ($this->server->send($fd, $chunk) === false) {
                        break;
                    }
                }
            }
        }

        $this->server->close($fd);
        $this->disablePayload();
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
