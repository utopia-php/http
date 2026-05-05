<?php

namespace Utopia\Http\Adapter;

use Swoole\Async;
use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server as Server;
use Utopia\DI\Container;
use Utopia\Http\Adapter;
use Utopia\Http\Adapter\Swoole\Mode;
use Utopia\Http\Adapter\Swoole\Request;
use Utopia\Http\Adapter\Swoole\Response;

class Swoole extends Adapter
{
    private const string CONTEXT_KEY = '__utopia__';

    public function __construct(
        private readonly Server $server,
        private readonly Container $resources
    )
    {
    }

    public function configure(Mode $mode, array $settings = []): void
    {
        $this->server->set([
            ...$mode->settings(),
            ...$settings,
        ]);

        if ($mode === Mode::HYPERLOOP_B) {
            Coroutine::set([Constant::OPTION_HOOK_FLAGS => SWOOLE_HOOK_ALL]);
        }
    }

    public function onRequest(callable $callback): void
    {
        $this->server->on('request', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            $context = new Container($this->resources);

            // TODO (@loks0n): `swooleRequest` and `swooleResponse` should be removed.
            // Any consumers using these should be updated to use the abstract request/response objects.
            $context->set('swooleRequest', fn() => $request);
            $context->set('swooleResponse', fn() => $response);

            Coroutine::getContext()[self::CONTEXT_KEY] = $context;

            \call_user_func($callback, new Request($request), new Response($response));
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
        $this->server->on('start', function () use ($callback) {
            if (Coroutine::getCid() === -1) {
                go(fn () => $callback($this));
            } else {
                $callback($this);
            }
        });
    }

    public function start(): void
    {
        $this->server->start();
    }
}
