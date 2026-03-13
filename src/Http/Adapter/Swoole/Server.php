<?php

namespace Utopia\Http\Adapter\Swoole;

use Swoole\Coroutine;
use Utopia\Http\Adapter;
use Utopia\DI\Container;
use Swoole\Coroutine\Http\Server as SwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

use function Swoole\Coroutine\run;

class Server extends Adapter
{
    protected SwooleServer $server;

    public function __construct(string $host, ?string $port = null, array $settings = [])
    {
        $this->server = new SwooleServer($host, $port);
        $this->server->set(\array_merge($settings, [
            'enable_coroutine' => true,
            'http_parse_cookie' => false,
        ]));
    }

    public function onRequest(callable $callback)
    {
        $this->server->handle('/', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            $context = \strval(Coroutine::getCid());
            $requestAdapter = new Request($request);
            $responseAdapter = new Response($response);
            $resources = [
                'swooleRequest' => $request,
                'swooleResponse' => $response,
            ];
            $configureRequestScope = function (Container $requestContainer) use ($request, $response) {
                $requestContainer
                    ->set('swooleRequest', fn () => $request)
                    ->set('swooleResponse', fn () => $response);
            };

            call_user_func($callback, $requestAdapter, $responseAdapter, $context, $resources, $configureRequestScope);
        });
    }

    public function onStart(callable $callback)
    {
        call_user_func($callback, $this);
    }

    public function start()
    {
        if (Coroutine::getCid() === -1) {
            run(fn () => $this->server->start());
        } else {
            $this->server->start();
        }
    }
}
