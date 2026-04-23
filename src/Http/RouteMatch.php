<?php

namespace Utopia\Http;

/**
 * Immutable, request-scoped result of a route match.
 *
 * Carries the registered Route plus per-match state so downstream code
 * can extract path params and report telemetry without mutating the
 * shared Route instance.
 */
final class RouteMatch
{
    public function __construct(
        public readonly Route $route,
        public readonly string $matchedPath,
        public readonly string $resolvedPath = '',
    ) {
    }
}
