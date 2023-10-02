<?php

namespace Utopia\Http\Adapter\Swoole;

use Swoole\Coroutine;
use Utopia\Http\Adapter;
use Swoole\Coroutine\Http\Server as SwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

class Server extends Adapter
{
    protected SwooleServer $server;

    public function __construct(string $host, string $port = null)
    {
        $this->server = new SwooleServer($host, $port);
        $this->server->set([
            'enable_coroutine' => true
        ]);
    }

    public function setConfig(array $configs)
    {
        $configs = array_merge($configs, [
            'enable_coroutine' => true
        ]);
        $this->server->set($configs);
    }

    public function onRequest(callable $callback)
    {
        $this->server->handle('/', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            call_user_func($callback, new Request($request), new Response($response), \strval(Coroutine::getCid()));
        });
    }

    public function onWorkerStart(callable $callback)
    {
        return;
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
