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
     * Return the global container — the singleton-scoped registry shared
     * across all requests. Use this for resources that should outlive a
     * single request (clients, configs, etc.).
     */
    abstract public function getContainer(): Container;

    /**
     * Return the per-request context container. Coroutine-local under the
     * Swoole adapters; identical to getContainer() under FPM. Use this for
     * values scoped to a single request — request, response, route,
     * matchedPath, error, etc. Lookups fall through to the global
     * container's parent chain, so getContext()->get('someSingleton')
     * still resolves.
     */
    abstract public function getContext(): Container;
}
