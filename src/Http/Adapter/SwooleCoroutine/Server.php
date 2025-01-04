<?php

namespace Utopia\Http\Adapter\SwooleCoroutine;

use Utopia\Http\Adapter;
use Swoole\Coroutine\Http\Server as SwooleServer;

class Server extends Adapter
{
    protected SwooleServer $server;

    public function __construct(string $host, ?string $port = null, array $settings = [])
    {
        $this->server = new SwooleServer($host, $port, false, true);
        $this->server->set(\array_merge($settings, [
            'enable_coroutine' => true
        ]));
    }

    public function onRequest(callable $callback)
    {
        $this->server->handle('/', function ($request, $response) use ($callback) {
            go(function () use ($request, $response, $callback) {
                call_user_func($callback, new Request($request), new Response($response));
            });
        });
    }

    public function onStart(callable $callback)
    {
        call_user_func($callback, $this);
    }

    public function start()
    {
        go(function () {
            $this->server->start();
        });
    }
}
