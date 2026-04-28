<?php

declare(strict_types=1);

namespace Utopia\Http;

/**
 * Bundle of everything the framework knows about how the current request
 * was routed: the matched Route, the prepared-path key it was matched
 * under (`$path`, with placeholders like ":::") and the name-keyed map
 * of resolved argument values.
 *
 * `$route` and `$path` are immutable. `$arguments` starts empty and is
 * written by the framework once — right before the route action runs,
 * after `getArguments()` has resolved and validated the action's
 * parameters. From that point on (action body, shutdown hooks, error
 * hooks) it holds the same values the action received.
 *
 * Init hooks run *before* this write, so they see `$arguments === []`.
 * The mutable property is safe because the RouteMatch lives on the
 * per-request context container (coroutine-local under the Swoole
 * adapters), so only the request's own coroutine touches it.
 *
 * Inject it into a hook or action with `->inject('match')`.
 */
final class RouteMatch
{
    /**
     * @param array<string, mixed> $arguments
     */
    public function __construct(
        public readonly Route $route,
        public readonly string $path,
        public array $arguments = [],
    ) {}
}
