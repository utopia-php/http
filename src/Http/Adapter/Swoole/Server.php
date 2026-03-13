<?php

namespace Utopia\Http\Adapter\Swoole;

use Swoole\Coroutine;
use Utopia\Http\Adapter;
use Utopia\DI\Container;
use Swoole\Coroutine\Http\Server as SwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

use function Swoole\Coroutine\run;

class Server extends Adapter
{
    protected SwooleServer $server;
    protected const string REQUEST_CONTAINER_CONTEXT_KEY = '__utopia_http_request_container';
    protected Container $container;

    public function __construct(string $host, ?string $port = null, array $settings = [], Container $container)
    {
        $this->server = new SwooleServer($host, $port);
        $this->server->set(\array_merge($settings, [
            'enable_coroutine' => true,
            'http_parse_cookie' => false,
        ]));
        $this->container = $container;
    }

    public function onRequest(callable $callback)
    {
        $this->server->handle('/', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            Coroutine::getContext()[self::REQUEST_CONTAINER_CONTEXT_KEY] = new Container($this->container);

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

        \call_user_func($callback, $this);
    }

    public function start()
    {
        if (Coroutine::getCid() === -1) {
            run(fn () => $this->server->start());
        } else {
            $this->server->start();
        }
    }
}
