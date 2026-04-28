<?php

declare(strict_types=1);

namespace Utopia\Http;

/**
 * Immutable bundle of everything the framework knows about how the current
 * request was routed: the matched Route, the prepared-path key it was
 * matched under (`$path`, with placeholders like ":::") and — once the
 * action has resolved its parameters — the name-keyed map of resolved +
 * validated argument values.
 *
 * Lives on the per-request context container (coroutine-local under the
 * Swoole adapters) under the key `'match'`. Inject it into a hook or
 * action with `->inject('match')`.
 */
final class RouteMatch
{
    /**
     * @param array<string, mixed> $arguments
     */
    public function __construct(
        public readonly Route $route,
        public readonly string $path,
        public readonly array $arguments = [],
    ) {}
}
