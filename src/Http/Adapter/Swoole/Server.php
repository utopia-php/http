<?php

namespace Utopia\Http\Adapter\Swoole;

use Utopia\Http\Adapter;
use Swoole\Http\Server as SwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

class Server extends Adapter
{
    protected SwooleServer $server;

    public function __construct(string $host, string $port = null)
    {
        parent::__construct();
        $this->server = new SwooleServer($host, $port);
    }

    public function setConfig(array $configs)
    {
        $this->server->set($configs);
    }

    public function onRequest(callable $callback)
    {
        $this->server->on('request', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            call_user_func($callback, new Request($request), new Response($response));
        });
    }

    public function onWorkerStart(callable $callback)
    {
        $this->server->on('WorkerStart', $callback);
    }

    public function onBeforeReload(callable $callback)
    {
        $this->server->on('BeforeReload', $callback);
    }

    public function onAfterReload(callable $callback)
    {
        $this->server->on('AfterReload', $callback);
    }

    public function onStart(callable $callback)
    {
        $this->server->on('start', $callback);
    }

    public function start()
    {
        $this->server->start();
    }
}
