<?php

namespace Utopia\Http\Adapter\FPM;

use Utopia\DI\Container;
use Utopia\Http\Adapter;

class Server extends Adapter
{
    public function __construct(private Container $resources) {}

    public function onRequest(callable $callback): void
    {
        $request = new Request();
        $response = new Response();

        $this->resources->set('fpmRequest', fn() => $request);
        $this->resources->set('fpmResponse', fn() => $response);

        \call_user_func($callback, $request, $response);
    }

    public function onStart(callable $callback): void
    {
        \call_user_func($callback, $this);
    }

    public function resources(): Container
    {
        return $this->resources;
    }

    public function context(): Container
    {
        return $this->resources;
    }

    public function start(): void {}
}
