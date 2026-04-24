<?php

namespace Utopia\Http\Adapter\FPM;

use Utopia\DI\Container;
use Utopia\Http\Adapter;

class Server extends Adapter
{
    public function __construct(private Container $container) {}

    public function onRequest(callable $callback)
    {
        $request = new Request();
        $response = new Response();

        $this->container->set('fpmRequest', fn() => $request);
        $this->container->set('fpmResponse', fn() => $response);

        \call_user_func($callback, $request, $response);
    }

    public function onStart(callable $callback)
    {
        \call_user_func($callback, $this);
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function start()
    {
        return;
    }
}
