<?php

namespace Utopia\Http\Adapter\SwooleCoroutine;

use Swoole\Coroutine;
use Utopia\Http\Adapter;
use Utopia\DI\Container;
use Swoole\Coroutine\Http\Server as SwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

class Server extends Adapter
{
    protected SwooleServer $server;
    protected const REQUEST_CONTAINER_CONTEXT_KEY = '__utopia_http_request_container';
    protected Container $container;
    /** @var callable|null */
    protected $onStartCallback = null;

    public function __construct(string $host, ?string $port = null, array $settings = [], ?Container $container = null)
    {
        $this->server = new SwooleServer($host, $port, false, true);
        $this->server->set(\array_merge($settings, [
            'http_parse_cookie' => false,
        ]));
        $this->container = $container ?? new Container();
    }

    public function onRequest(callable $callback)
    {
        $this->server->handle('/', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            go(function () use ($request, $response, $callback) {
                $requestContainer = new Container($this->container);
                $requestContainer->set('swooleRequest', fn () => $request);
                $requestContainer->set('swooleResponse', fn () => $response);

                Coroutine::getContext()[self::REQUEST_CONTAINER_CONTEXT_KEY] = $requestContainer;

                \call_user_func($callback, new Request($request), new Response($response));
            });
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
        $this->onStartCallback = $callback;
    }

    public function start()
    {
        go(function () {
            if ($this->onStartCallback) {
                \call_user_func($this->onStartCallback, $this);
            }
            $this->server->start();
        });
    }
}
