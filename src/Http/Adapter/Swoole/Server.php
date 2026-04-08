<?php

namespace Utopia\Http\Adapter\Swoole;

use Swoole\Coroutine;
use Utopia\Http\Adapter;
use Utopia\DI\Container;
use Swoole\Http\Server as SwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

class Server extends Adapter
{
    protected SwooleServer $server;
    protected const REQUEST_CONTAINER_CONTEXT_KEY = '__utopia_http_request_container';
    protected Container $container;

    public function __construct(string $host, ?string $port = null, array $settings = [], int $mode = SWOOLE_PROCESS, ?Container $container = null)
    {
        $this->server = new SwooleServer($host, (int) $port, $mode);
        $this->server->set($settings);
        $this->container = $container ?? new Container();
    }

    public function onRequest(callable $callback)
    {
        $this->server->on('request', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            $requestContainer = new Container($this->container);
            $requestContainer->set('swooleRequest', fn () => $request);
            $requestContainer->set('swooleResponse', fn () => $response);

            Coroutine::getContext()[self::REQUEST_CONTAINER_CONTEXT_KEY] = $requestContainer;

            \call_user_func($callback, new Request($request), new Response($response));
        });
    }

    public function getContainer(): Container
    {
        if (Coroutine::getCid() !== -1) {
            return Coroutine::getContext()[self::REQUEST_CONTAINER_CONTEXT_KEY] ?? $this->container;
        }

        return $this->container;
    }

    public function getServer(): SwooleServer
    {
        return $this->server;
    }

    public function onStart(callable $callback)
    {
        $this->server->on('start', function () use ($callback) {
            go(function () use ($callback) {
                \call_user_func($callback, $this);
            });
        });
    }

    public function start()
    {
        return $this->server->start();
    }
}
