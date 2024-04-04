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

    public function __construct(string $host, string $port = null, array $settings = [])
    {
        $this->server = new SwooleServer($host, $port);
        $this->server->set(\array_merge($settings, [
            'enable_coroutine' => true
        ]));
    }

    public function onRequest(callable $callback)
    {
        $this->server->handle('/', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            call_user_func($callback, new Request($request), new Response($response));
        });
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
