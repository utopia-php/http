<?php

namespace Utopia\Http;

/**
 * Immutable result of {@see Router::match()}.
 *
 * Carries the matched Route together with the route key it matched against
 * (the registered template after placeholder substitution, '*' for a wildcard,
 * or '' for the method-agnostic wildcard). Returning a value object instead of
 * mutating the Route avoids racing the shared Route under coroutines.
 */
final readonly class RouteMatch
{
    public function __construct(
        /**
         * The matched Route — the registered handler for this request.
         */
        public Route $route,
        /**
         * Path params resolved from the request URL against the matched
         * template (e.g. `['id' => 'abc-123']` for `/users/:id` matching
         * `/users/abc-123`). Empty for static routes and wildcards.
         *
         * @var array<string, string>
         */
        public array $params,
    ) {
    }
}
