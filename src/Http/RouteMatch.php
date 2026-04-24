<?php

declare(strict_types=1);

namespace Utopia\Http;

/**
 * Immutable result of matching a request against the router.
 *
 * Carries per-request facts so the shared {@see Route} definition is never
 * mutated by the matching process. Safe to share across coroutines.
 */
final readonly class RouteMatch
{
    public function __construct(
        public Route $route,
        public string $urlPath,
        public string $routeKey,
        public string $preparedPath,
    ) {}
}
