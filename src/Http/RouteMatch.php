<?php

declare(strict_types=1);

namespace Utopia\Http;

/**
 * Immutable bundle of everything the framework knows about how the current
 * request was routed: the matched Route, the prepared-path key it was
 * matched under (with placeholders like ":::") and — once the action has
 * resolved its parameters — the name-keyed map of resolved + validated
 * argument values.
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
        public readonly string $matchedPath,
        public readonly array $arguments = [],
    ) {}

    /**
     * Return a copy of this match with the resolved-argument map replaced.
     * Used by the framework once the action's parameters have been
     * validated, so subsequent shutdown / error hooks can read the same
     * values the action received.
     *
     * @param array<string, mixed> $arguments
     */
    public function withArguments(array $arguments): self
    {
        return new self($this->route, $this->matchedPath, $arguments);
    }
}
