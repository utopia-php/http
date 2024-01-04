<?php

namespace Utopia\Http\Adapter\Swoole;

use Swoole\Coroutine;
use Utopia\Http\Adapter;
use Swoole\Coroutine\Http\Server as SwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Utopia\Http\Http;

use function Swoole\Coroutine\run;

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
            $context = \strval(Coroutine::getCid());

            Http::setResource('swooleRequest', fn () => $request, [], $context);
            Http::setResource('swooleResponse', fn () => $response, [], $context);

            call_user_func($callback, new Request($request), new Response($response), $context);
        });
    }

    public function onWorkerStart(callable $callback)
    {
        call_user_func($callback, $this);
    }

    public function onStart(callable $callback)
    {
        call_user_func($callback, $this);
    }

    public function start()
    {
        if(Coroutine::getCid() === -1) {
            run(fn () => $this->server->start());
        } else {
            $this->server->start();
        }
    }
}
