<?php

namespace Utopia\Adapter\Swoole;

use Utopia\Adapter;
use Swoole\Http\Server as SwooleServer;
use Utopia\Request as UtopiaRequest;
use Utopia\Response as UtopiaResponse;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

class Server extends Adapter
{
    protected SwooleServer $server;

    public function __construct(string $host, string $port = null)
    {
        $this->server = new SwooleServer($host, $port);
    }

    public function getRequest(): UtopiaRequest
    {
        return new Request(new SwooleRequest());
    }

    public function getResponse(): UtopiaResponse
    {
        return new Response(new SwooleResponse());
    }

    public function setConfig(array $configs)
    {
        $this->server->set($configs);
    }

    public function onWorkerStart(callable $callback)
    {
        $this->server->on('WorkerStart', $callback);
    }
}
