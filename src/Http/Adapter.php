<?php

declare(strict_types=1);

namespace Utopia\Http;

use Utopia\DI\Container;

abstract class Adapter
{
    abstract public function onStart(callable $callback): void;
    abstract public function onRequest(callable $callback): void;
    abstract public function start(): void;

    /**
     * Container for the current execution context: the per-request
     * container inside a request (coroutine-local under Swoole, with
     * parent-chain fallback to global), the global container otherwise.
     */
    abstract public function getContext(): Container;
}
