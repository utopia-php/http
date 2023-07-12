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
        parent::__construct(\microtime(true));
    }

    /**
     * Write
     *
     * @param  string  $content
     * @return void
     */
    protected function write(string $content): void
    {
        $this->swoole->write($content);
    }

    /**
     * End
     *
     * @param  string|null  $content
     * @return void
     */
    protected function end(string $content = null): void
    {
        $this->swoole->end($content);
    }

    /**
     * Send Status Code
     *
     * @param  int  $statusCode
     * @return void
     */
    protected function sendStatus(int $statusCode): void
    {
        $this->swoole->status((string) $statusCode);
    }

    /**
     * Send Header
     *
     * @param  string  $key
     * @param  string  $value
     * @return void
     */
    protected function sendHeader(string $key, string $value): void
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
            name: $name,
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
