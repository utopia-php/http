<?php

namespace Utopia\Http\Adapter\SwooleCoroutine;

use Utopia\Http\Adapter;
use Swoole\Coroutine\Http\Server as SwooleServer;

use function Swoole\Coroutine\run;

class Server extends Adapter
{
    protected SwooleServer $server;

    public function __construct(string $host, string $port = null, array $settings = [])
    {
        $this->server = new SwooleServer($host, $port);
        $this->server->set(\array_merge($settings, [
            'enable_coroutine' => true
        ]));
    }

    public function onRequest(callable $callback)
    {
        $this->server->handle('/', function ($request, $response) use ($callback) {
            call_user_func($callback, new Request($request), new Response($response));
        });
    }

    public function onStart(callable $callback)
    {
        go(function () use ($callback) {
            call_user_func($callback, $this);
        });
    }

    public function start()
    {
        go(function () {
            $this->server->start();
        });
    }
}
