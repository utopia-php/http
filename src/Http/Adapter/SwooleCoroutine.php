<?php

namespace Utopia\Http\Adapter;

use Swoole\Coroutine;
use Swoole\Coroutine\Http\Server;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Utopia\DI\Container;
use Utopia\Http\Adapter;
use Utopia\Http\Adapter\Swoole\Mode;
use Utopia\Http\Adapter\Swoole\Request;
use Utopia\Http\Adapter\Swoole\Response;

class SwooleCoroutine extends Adapter
{
    private const string CONTEXT_KEY = '__utopia__';

    /** @var array<callable> */
    protected $onStart = [];

    public function __construct(
        private readonly Server $server,
        private readonly Container $resources
    ) {
    }

    public function configure(array $settings = []): void
    {
        $this->server->set([
            ...Mode::defaults(),
            ...$settings,
        ]);
    }

    public function onRequest(callable $callback): void
    {
        $this->server->handle('/', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            $context = new Container($this->resources);

            // TODO (@loks0n): `swooleRequest` and `swooleResponse` should be removed.
            // Any consumers using these should be updated to use the abstract request/response objects.
            $context->set('swooleRequest', fn() => $request);
            $context->set('swooleResponse', fn() => $response);

            Coroutine::getContext()[self::CONTEXT_KEY] = $context;

            try {
                \call_user_func($callback, new Request($request), new Response($response));
            } finally {
                unset(Coroutine::getContext()[self::CONTEXT_KEY]);
            }
        });
    }

    public function getContext(): Container
    {
        if (Coroutine::getCid() !== -1) {
            return Coroutine::getContext()[self::CONTEXT_KEY] ?? $this->resources;
        }

        return $this->resources;
    }

    public function onStart(callable $callback): void
    {
        $this->onStart[] = $callback;
    }

    public function start(): void
    {
        foreach ($this->onStart as $callback) {
            if (Coroutine::getCid() === -1) {
                go(fn () => $callback($this));
            } else {
                $callback($this);
            }
        }

        $this->server->start();
    }
}
