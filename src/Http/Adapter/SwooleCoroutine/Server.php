<?php

namespace Utopia\Http\Adapter\SwooleCoroutine;

use Swoole\Coroutine;
use Swoole\Coroutine\Http\Server as SwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Utopia\DI\Container;
use Utopia\Http\Adapter;

class Server extends Adapter
{
    protected const string REQUEST_CONTAINER_CONTEXT_KEY = '__utopia_http_request_container';

    protected SwooleServer $server;
    protected Container $container;

    /** @var callable|null */
    protected $onStartCallback;

    /**
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        string $host,
        ?string $port = null,
        array $settings = [],
        ?Container $container = null,
    ) {
        $this->server = new SwooleServer($host, $port, false, true);
        $this->server->set($settings);
        $this->container = $container ?? new Container();
    }

    public function onRequest(callable $callback): void
    {
        $this->server->handle('/', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            $requestContainer = new Container($this->container);
            $requestContainer->set('swooleRequest', fn() => $request);
            $requestContainer->set('swooleResponse', fn() => $response);

            Coroutine::getContext()[self::REQUEST_CONTAINER_CONTEXT_KEY] = $requestContainer;

            try {
                \call_user_func($callback, new Request($request), new Response($response));
            } finally {
                unset(Coroutine::getContext()[self::REQUEST_CONTAINER_CONTEXT_KEY]);
            }
        });
    }

    public function getContainer(): Container
    {
        return Coroutine::getContext()[self::REQUEST_CONTAINER_CONTEXT_KEY] ?? $this->container;
    }

    public function getServer(): SwooleServer
    {
        return $this->server;
    }

    public function onStart(callable $callback): void
    {
        $this->onStartCallback = $callback;
    }

    public function start(): void
    {
        if ($this->onStartCallback) {
            \call_user_func($this->onStartCallback, $this);
        }

        $this->server->start();
    }
}
