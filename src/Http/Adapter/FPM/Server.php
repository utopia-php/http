<?php

namespace Utopia\Http\Adapter\FPM;

use Utopia\DI\Container;
use Utopia\Http\Adapter;

class Server extends Adapter
{
    public function __construct()
    {
    }

    public function onRequest(callable $callback)
    {
        $request = new Request();
        $response = new Response();
        $resources = [
            'fpmRequest' => $request,
            'fpmResponse' => $response,
        ];
        $configureRequestScope = function (Container $requestContainer) use ($request, $response) {
            $requestContainer
                ->set('fpmRequest', fn () => $request, [])
                ->set('fpmResponse', fn () => $response, []);
        };

        call_user_func($callback, $request, $response, 'fpm', $resources, $configureRequestScope);
    }

    public function onStart(callable $callback)
    {
        call_user_func($callback, $this);
    }

    public function start()
    {
        return;
    }
}
