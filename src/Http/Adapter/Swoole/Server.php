<?php

namespace Utopia\Http\Adapter\Swoole;

use Swoole\Coroutine;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server as SwooleServer;
use Utopia\DI\Container;
use Utopia\Http\Adapter;

class Server extends Adapter
{
    protected SwooleServer $server;
    protected const string CONTEXT_KEY = '__utopia__';

    /**
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        string $host,
        ?string $port = null,
        array $settings = [],
        int $mode = SWOOLE_PROCESS,
        protected Container $resources = new Container(),
    ) {
        $this->server = new SwooleServer($host, (int) $port, $mode);
        $this->server->set($settings);
    }

    public function onRequest(callable $callback): void
    {
        $this->server->on('request', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            $context = new Container($this->resources);
            $context->set('swooleRequest', fn() => $request);
            $context->set('swooleResponse', fn() => $response);

            Coroutine::getContext()[self::CONTEXT_KEY] = $context;

            \call_user_func($callback, new Request($request), new Response($response));
        });
    }

    public function resources(): Container
    {
        return $this->resources;
    }

    public function context(): Container
    {
        if (Coroutine::getCid() !== -1) {
            return Coroutine::getContext()[self::CONTEXT_KEY] ?? $this->resources;
        }

        return $this->resources;
    }

    public function getServer(): SwooleServer
    {
        return $this->server;
    }

    public function onStart(callable $callback): void
    {
        $this->server->on('start', function () use ($callback) {
            go(function () use ($callback) {
                \call_user_func($callback, $this);
            });
        });
    }

    public function start(): void
    {
        $this->server->start();
    }
}
