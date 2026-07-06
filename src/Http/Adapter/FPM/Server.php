<?php

declare(strict_types=1);

namespace Utopia\Http\Adapter\FPM;

use Utopia\DI\Container;
use Utopia\Http\Adapter;

class Server extends Adapter
{
    private ?Container $context = null;

    private RequestFactory $requestFactory;

    public function __construct(private Container $resources)
    {
        $this->requestFactory = new RequestFactory();
    }

    public function onRequest(callable $callback): void
    {
        $request = $this->requestFactory->create();
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
