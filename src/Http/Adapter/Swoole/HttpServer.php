<?php

namespace Utopia\Http\Adapter\Swoole;

use Swoole\Coroutine;
use Utopia\Http\Adapter;
use Utopia\DI\Container;
use Swoole\Http\Server as SwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

class HttpServer extends Adapter
{
    protected SwooleServer $server;
    protected const REQUEST_CONTAINER_CONTEXT_KEY = '__utopia_http_request_container';
    protected Container $container;

    public function __construct(string $host, int $port, array $settings = [], ?Container $container = null)
    {
        $this->server = new SwooleServer($host, $port);
        $this->server->set(\array_merge($settings, [
            'enable_coroutine' => true,
            'http_parse_cookie' => false,
        ]));
        $this->container = $container ?? new Container();
    }

    public function onRequest(callable $callback)
    {
        $this->server->on('request', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            $requestContainer = new Container($this->container);
            $requestContainer->set('swooleRequest', fn () => $request);
            $requestContainer->set('swooleResponse', fn () => $response);

            Coroutine::getContext()[self::REQUEST_CONTAINER_CONTEXT_KEY] = $requestContainer;

            $utopiaRequest = new Request($request);
            $utopiaResponse = new Response($response);

            \call_user_func($callback, $utopiaRequest, $utopiaResponse);
        });
    }

    public function getContainer(): Container
    {
        return Coroutine::getContext()[self::REQUEST_CONTAINER_CONTEXT_KEY] ?? $this->container;
    }

    public function onStart(callable $callback)
    {
        $this->server->on('start', function () use ($callback) {
            \call_user_func($callback, $this);
        });
    }

    public function start()
    {
        $this->server->start();
    }
}
