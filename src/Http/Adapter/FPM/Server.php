<?php

namespace Utopia\Http\Adapter\FPM;

use Utopia\DI\Container;
use Utopia\Http\Adapter;

class Server extends Adapter
{
    private ?Container $context = null;

    public function __construct(private Container $resources) {}

    public function onRequest(callable $callback): void
    {
        $request = new Request();
        $response = new Response();

        $this->context = new Container($this->resources);
        $this->context->set('fpmRequest', fn() => $request);
        $this->context->set('fpmResponse', fn() => $response);

        try {
            \call_user_func($callback, $request, $response);
        } finally {
            $this->context = null;
        }
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
        return $this->context ?? $this->resources;
    }

    public function start(): void {}
}
