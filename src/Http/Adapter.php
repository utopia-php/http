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
     * Return the container for the current execution context:
     *
     * - Inside a request, the per-request container (coroutine-local
     *   under the Swoole adapters), with parent-chain fallback to the
     *   global container — so singleton lookups still resolve.
     * - Outside a request (boot, onStart hooks), the global container
     *   directly.
     *
     * Callers don't need to know which "scope" they're in; they get
     * the right container for where they are.
     */
    abstract public function getContext(): Container;
}
