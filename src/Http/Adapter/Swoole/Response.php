<?php

namespace Utopia\Http\Adapter\Swoole;

use Swoole\Http\Response as SwooleResponse;
use Utopia\Http\Response as UtopiaResponse;

class Response extends UtopiaResponse
{
    /**
     * Swoole Response Object
     *
     * @var SwooleResponse
     */
    protected $swoole;

    /**
     * Response constructor.
     */
    public function __construct(SwooleResponse $response)
    {
        $this->swoole = $response;
        parent::__construct(microtime(true));
    }

    public function getSwooleResponse(): SwooleResponse
    {
        return $this->swoole;
    }

    /**
     * Write
     *
     * @return bool False if write cannot complete, such as request ended by client
     */
    public function write(string $content): bool
    {
        return $this->swoole->write($content);
    }

    /**
     * End
     */
    public function end(?string $content = null): void
    {
        $this->swoole->end($content);
    }

    /**
     * Send Status Code
     */
    protected function sendStatus(int $statusCode): void
    {
        $this->swoole->status((string) $statusCode);
    }

    /**
     * Send Header
     *
     * @param  string|array<string>  $value
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
     * @param  array<string, mixed>  $options
     */
    protected function sendCookie(string $name, string $value, array $options): void
    {
        $this->swoole->cookie(
            $name,
            $value,
            $options['expire'] ?? 0,
            $options['path'] ?? '',
            $options['domain'] ?? '',
            $options['secure'] ?? false,
            $options['httponly'] ?? false,
            $options['samesite'] ?? false,
        );
    }
}
