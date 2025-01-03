<?php

namespace Utopia\Http\Adapter\Swoole;

use Utopia\Http\Adapter;
use Swoole\Http\Server as SwooleServer;
use Swoole\Runtime;

class Server extends Adapter
{
    protected SwooleServer $server;

    public function __construct(string $host, ?string $port = null, array $settings = [])
    {
        $this->server = new SwooleServer($host, $port);
        $this->server->set(\array_merge($settings, [
            'open_http2_protocol' => true,
            'dispatch_mode' => 2,
        ]));
    }

    public function onRequest(callable $callback)
    {
        $this->server->on('request', function ($request, $response) use ($callback) {
            go(function () use ($request, $response, $callback) {
                call_user_func($callback, new Request($request), new Response($response));
            });
        });
    }

    public function onStart(callable $callback)
    {
        $this->server->on('start', function () use ($callback) {
            go(function () use ($callback) {
                call_user_func($callback);
            });
        });
    }

    public function start()
    {
        Runtime::enableCoroutine();
        return $this->server->start();
    }
}
