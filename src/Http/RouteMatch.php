<?php

declare(strict_types=1);

namespace Utopia\Http;

use Utopia\Servers\Hook;

/**
 * The result of matching a request against the registered routes.
 */
final readonly class RouteMatch
{
    public function __construct(
        /**
         * The handler that will run for this request — usually a {@see Route},
         * or the wildcard fallback (a {@see Hook}) registered via
         * {@see Http::wildcard()} when no route matched.
         *
         * Use `instanceof Route` to access route-only fields like
         * `getMethod()` / `getPath()`.
         */
        public Hook $route,
        /**
         * Path params parsed from the request URL.
         *
         * For example `['id' => 'abc-123']` when `/users/:id` matches
         * `/users/abc-123`. Empty for static routes and wildcards.
         *
         * @var array<string, string>
         */
        public array $params,
    ) {}
}
