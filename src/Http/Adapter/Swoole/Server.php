<?php

namespace Utopia\Http\Adapter\Swoole;

use Utopia\Http\Adapter\Adapter;
use Swoole\Http\Server as SwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Utopia\Http\Http;

class Server extends Adapter
{
    protected SwooleServer $server;

    public function __construct(string $host, ?string $port = null, array $settings = [])
    {
        $this->server = new SwooleServer($host, $port);
        if (!empty($settings)) {
            $this->server->set($settings);
        }
    }

    public function onRequest(callable $callback)
    {
        $this->server->on('request', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            Http::setResource('swooleRequest', fn () => $request);
            Http::setResource('swooleResponse', fn () => $response);

            call_user_func($callback, new Request($request), new Response($response));
        });
    }

    public function onStart(callable $callback)
    {
        call_user_func($callback, $this);
    }

    public function start()
    {
        $this->server->start();
    }
}
