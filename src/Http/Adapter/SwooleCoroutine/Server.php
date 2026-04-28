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
    protected const string CONTEXT_KEY = '__utopia_http_context';

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
            $context = new Container($this->container);
            $context->set('swooleRequest', fn() => $request);
            $context->set('swooleResponse', fn() => $response);

            Coroutine::getContext()[self::CONTEXT_KEY] = $context;

            try {
                \call_user_func($callback, new Request($request), new Response($response));
            } finally {
                unset(Coroutine::getContext()[self::CONTEXT_KEY]);
            }
        });
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getContext(): Container
    {
        return Coroutine::getContext()[self::CONTEXT_KEY] ?? $this->container;
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
