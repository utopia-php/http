<?php

namespace Utopia\Http;

use Exception;

class Router
{
    /**
     * Placeholder token for params in paths.
     */
    public const PLACEHOLDER_TOKEN = ':::';
    public const WILDCARD_TOKEN = '*';

    protected static bool $allowOverride = false;

    /**
     * @var array<string,Route[]>
     */
    protected static array $routes = [
        Http::REQUEST_METHOD_GET => [],
        Http::REQUEST_METHOD_POST => [],
        Http::REQUEST_METHOD_PUT => [],
        Http::REQUEST_METHOD_PATCH => [],
        Http::REQUEST_METHOD_DELETE => [],
    ];

    /**
     * Contains the positions of all params in the paths of all registered Routes.
     *
     * @var array<int>
     */
    protected static array $params = [];

    /**
     * Method-agnostic wildcard route, used as last-resort fallback when no
     * method-specific route matches. Registered via {@see self::setWildcard()}.
     */
    protected static ?Route $wildcard = null;

    /**
     * Get all registered routes.
     *
     * @return array<string, Route[]>
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Get allow override
     *
     */
    public static function getAllowOverride(): bool
    {
        return self::$allowOverride;
    }

    /**
     * Set Allow override
     *
     */
    public static function setAllowOverride(bool $value): void
    {
        self::$allowOverride = $value;
    }


    /**
     * Add route to router.
     *
     * @throws \Exception
     */
    public static function addRoute(Route $route): void
    {
        [$path, $params] = self::preparePath($route->getPath());

        if (!array_key_exists($route->getMethod(), self::$routes)) {
            throw new Exception("Method ({$route->getMethod()}) not supported.");
        }

        if (array_key_exists($path, self::$routes[$route->getMethod()]) && !self::$allowOverride) {
            throw new Exception("Route for ({$route->getMethod()}:{$path}) already registered.");
        }

        foreach ($params as $key => $index) {
            $route->setPathParam($key, $index, $path);
        }

        self::$routes[$route->getMethod()][$path] = $route;
    }

    /**
     * Add route to router.
     *
     * @throws \Exception
     */
    public static function addRouteAlias(string $path, Route $route): void
    {
        [$alias, $params] = self::preparePath($path);

        if (array_key_exists($alias, self::$routes[$route->getMethod()]) && !self::$allowOverride) {
            throw new Exception("Route for ({$route->getMethod()}:{$alias}) already registered.");
        }

        foreach ($params as $key => $index) {
            $route->setPathParam($key, $index, $alias);
        }

        self::$routes[$route->getMethod()][$alias] = $route;
    }

    /**
     * Register a method-agnostic wildcard route. Used as the last-resort
     * fallback when no method-specific route matches. At most one wildcard
     * can be registered; a subsequent call replaces the previous one.
     */
    public static function setWildcard(Route $route): void
    {
        self::$wildcard = $route;
    }

    /**
     * Match a {@see Request} against the router.
     *
     * Extracts the path from the request URI, normalises HEAD to GET (the
     * HEAD method is served by the GET handler with the response payload
     * disabled), and returns an immutable {@see RouteMatch} carrying the
     * matched route and per-request match facts. The shared {@see Route}
     * definition is never mutated.
     */
    public static function matchRequest(Request $request): ?RouteMatch
    {
        $url = \parse_url($request->getURI(), PHP_URL_PATH);
        $url = \is_string($url) ? ($url === '' ? '/' : $url) : '/';

        $method = $request->getMethod();
        $method = ($method === Http::REQUEST_METHOD_HEAD) ? Http::REQUEST_METHOD_GET : $method;

        return self::matchRoute($method, $url);
    }

    /**
     * Match against a (method, path) pair. Internal — application code should
     * call {@see self::matchRequest()} so URL parsing and HEAD normalisation
     * are handled consistently.
     */
    private static function matchRoute(string $method, string $path): ?RouteMatch
    {
        if (array_key_exists($method, self::$routes)) {
            $parts = array_values(array_filter(explode('/', $path), fn($segment) => $segment !== ''));
            $length = count($parts) - 1;
            $filteredParams = array_filter(self::$params, fn($i) => $i <= $length);

            foreach (self::combinations($filteredParams) as $sample) {
                $sample = array_filter($sample, fn(int $i) => $i <= $length);
                $match = implode(
                    '/',
                    array_replace(
                        $parts,
                        array_fill_keys($sample, self::PLACEHOLDER_TOKEN),
                    ),
                );

                if (array_key_exists($match, self::$routes[$method])) {
                    return new RouteMatch(self::$routes[$method][$match], $path, $match, $match);
                }
            }

            /**
             * Match root wildcard for this method (e.g. GET /*).
             */
            $match = self::WILDCARD_TOKEN;
            if (array_key_exists($match, self::$routes[$method])) {
                return new RouteMatch(self::$routes[$method][$match], $path, $match, $match);
            }

            /**
             * Match wildcard for path segments (e.g. GET /foo/*).
             */
            foreach ($parts as $part) {
                $current = ($current ?? '') . "{$part}/";
                $match = $current . self::WILDCARD_TOKEN;
                if (array_key_exists($match, self::$routes[$method])) {
                    return new RouteMatch(self::$routes[$method][$match], $path, $match, $match);
                }
            }
        }

        /**
         * Fall through to the method-agnostic wildcard registered via
         * {@see self::setWildcard()}.
         */
        if (self::$wildcard !== null) {
            return new RouteMatch(self::$wildcard, $path, self::WILDCARD_TOKEN, self::WILDCARD_TOKEN);
        }

        return null;
    }

    /**
     * Get all combinations of the given set.
     *
     * @param array<int, mixed> $set
     * @return iterable<array<int, mixed>>
     */
    protected static function combinations(array $set): iterable
    {
        yield [];

        $results = [[]];

        foreach ($set as $element) {
            foreach ($results as $combination) {
                $ret = array_merge([$element], $combination);
                $results[] = $ret;

                yield $ret;
            }
        }
    }

    /**
     * Prepare path for matching
     *
     * @return array{0: string, 1: array<string, int>}
     */
    public static function preparePath(string $path): array
    {
        $parts = array_values(array_filter(explode('/', $path)));
        $prepare = '';
        $params = [];

        foreach ($parts as $key => $part) {
            if ($key !== 0) {
                $prepare .= '/';
            }

            if (str_starts_with($part, ':')) {
                $prepare .= self::PLACEHOLDER_TOKEN;
                $params[ltrim($part, ':')] = $key;
                if (!in_array($key, self::$params)) {
                    self::$params[] = $key;
                }
            } else {
                $prepare .= $part;
            }
        }

        return [$prepare, $params];
    }

    /**
     * Reset router
     */
    public static function reset(): void
    {
        self::$params = [];
        self::$wildcard = null;
        self::$routes = [
            Http::REQUEST_METHOD_GET => [],
            Http::REQUEST_METHOD_POST => [],
            Http::REQUEST_METHOD_PUT => [],
            Http::REQUEST_METHOD_PATCH => [],
            Http::REQUEST_METHOD_DELETE => [],
        ];
    }
}
