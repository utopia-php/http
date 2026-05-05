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
         * The route key this request matched against: the registered template
         * after placeholder substitution (e.g. `users/:::` for `/users/:id`),
         * `*` for a method-specific wildcard, or `''` for the method-agnostic
         * wildcard set via {@see Router::setWildcard()}.
         *
         * Used as the key into {@see Route::getPathValues()} to resolve path
         * params for the matched template (a single Route can be registered
         * under multiple templates via aliases).
         */
        public string $path,
    ) {
    }
}
