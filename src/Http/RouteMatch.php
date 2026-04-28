<?php

declare(strict_types=1);

namespace Utopia\Http;

/**
 * Bundle of everything the framework knows about how the current request
 * was routed: the matched Route, the prepared-path key it was matched
 * under (`$path`, with placeholders like ":::") and the name-keyed map
 * of argument values for the current phase.
 *
 * `$route` and `$path` are immutable. `$arguments` is filled in two
 * passes during a request — path values at match-time so init hooks can
 * read them, then the full resolved+validated set right before the
 * action runs — and is therefore mutable. Safe because the RouteMatch
 * lives on the per-request context container (coroutine-local under
 * the Swoole adapters), so only the request's own coroutine touches it.
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
