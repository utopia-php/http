<?php

namespace Utopia\Http\Adapter\Swoole;

use Swoole\Coroutine;
use Utopia\Http\Adapter;
use Swoole\Coroutine\Http\Server as SwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

use function Swoole\Coroutine\run;

class Server extends Adapter
{
    protected SwooleServer $server;

    public function __construct(string $host, string $port = null, array $settings = [])
    {
        $workerNumber = swoole_cpu_num() * 6;
        $this->server = new SwooleServer($host, $port);
        $this->server->set(\array_merge($settings, [
            'open_http2_protocol' => true,
            // 'http_compression' => true,
            // 'http_compression_level' => 6,

            // Server
            // 'log_level' => 2,
            'dispatch_mode' => 3,
            'worker_num' => $workerNumber,
            'reactor_num' => swoole_cpu_num() * 2,
            'task_worker_num' => $workerNumber,
            'open_cpu_affinity' => true,

            // Coroutine
            'enable_coroutine' => true,
            'max_coroutine' => 300000,
        ]));
    }

    public function onRequest(callable $callback)
    {
        $this->server->handle('/', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            call_user_func($callback, new Request($request), new Response($response));
            // go(function () use ($request, $response, $callback) {
            // });
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
