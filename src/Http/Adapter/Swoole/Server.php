<?php

namespace Utopia\Http\Adapter\Swoole;

use Utopia\Http\Adapter;
use Swoole\Http\Server as SwooleServer;
use Swoole\Runtime;

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
            // 'log_level' => 0,
            'dispatch_mode' => 2,
            // 'worker_num' => $workerNumber,
            // 'reactor_num' => swoole_cpu_num() * 2,
            // 'task_worker_num' => $workerNumber,
            // 'open_cpu_affinity' => true,

            // Coroutine
            // 'enable_coroutine' => true,
            // 'max_coroutine' => 1000,
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
        $this->server->on('request', function () use ($callback) {
            go(function () use ($callback) {
                call_user_func($callback, $this);
            });
        });
    }

    public function start()
    {
        Runtime::enableCoroutine();
        return $this->server->start();
    }
}
