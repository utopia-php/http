<?php

namespace Utopia\Http\Router;

use Utopia\Http\Route;

/**
 * Immutable result of {@see \Utopia\Http\Router::match()}.
 *
 * Carries the matched Route together with the route key it matched against
 * (the registered template after placeholder substitution, '*' for a wildcard,
 * or '' for the method-agnostic wildcard). Returning a value object instead of
 * mutating the Route avoids racing the shared Route under coroutines.
 */
final readonly class Result
{
    public function __construct(
        public Route $route,
        public string $matchedPath,
    ) {
    }
}
