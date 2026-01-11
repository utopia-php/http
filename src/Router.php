<?php

namespace Utopia;

use Exception;

class Router
{
    public const string WILDCARD_TOKEN = '*';
    public const string PLACEHOLDER_TOKEN = ':::';
    public const int ROUTE_MATCH_CACHE_LIMIT = 10_000;

    protected static bool $allowOverride = false;

    /** @var array<string,Route[]> */
    protected static array $routes = [
        App::REQUEST_METHOD_GET => [],
        App::REQUEST_METHOD_POST => [],
        App::REQUEST_METHOD_PUT => [],
        App::REQUEST_METHOD_PATCH => [],
        App::REQUEST_METHOD_DELETE => [],
    ];

    /** @var array<int> */
    protected static array $params = [];

    /** @var array<string,RouterTrie> */
    protected static array $tries = [];

    /** @var array<string,array{route:Route|null,pattern:string}|false> */
    protected static array $matchCache = [];

    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Get allow override
     *
     *
     * @return bool
     */
    public static function getAllowOverride(): bool
    {
        return self::$allowOverride;
    }

    /**
     * Set Allow override
     *
     *
     * @param  bool  $value
     * @return void
     */
    public static function setAllowOverride(bool $value): void
    {
        self::$allowOverride = $value;
    }


    /**
     * Add route to router.
     *
     * @param Route $route
     * @return void
     * @throws Exception
     */
    public static function addRoute(Route $route): void
    {
        if (!array_key_exists($route->getMethod(), self::$routes)) {
            throw new Exception("Method ({$route->getMethod()}) not supported.");
        }

        [$path, $params] = self::preparePath($route->getPath());
        self::registerRoute($route, $route->getPath(), $path, $params);
    }

    /**
     * @throws Exception
     */
    public static function addRouteAlias(string $path, Route $route): void
    {
        if (!array_key_exists($route->getMethod(), self::$routes)) {
            throw new Exception("Method ({$route->getMethod()}) not supported.");
        }

        [$alias, $params] = self::preparePath($path);
        self::registerRoute($route, $path, $alias, $params);
    }

    /**
     * @throws Exception
     */
    protected static function registerRoute(Route $route, string $originalPath, string $pattern, array $params): void
    {
        if (isset(self::$routes[$route->getMethod()][$pattern]) && !self::$allowOverride) {
            throw new Exception("Route for ({$route->getMethod()}:$pattern) already registered.");
        }

        foreach ($params as $key => $index) {
            $route->setPathParam($key, $index, $pattern);
        }

        self::$routes[$route->getMethod()][$pattern] = $route;

        if (!isset(self::$tries[$route->getMethod()])) {
            self::$tries[$route->getMethod()] = new RouterTrie();
        }

        if (!str_contains($originalPath, self::WILDCARD_TOKEN)) {
            $segments = array_values(array_filter(explode('/', $originalPath)));
            self::$tries[$route->getMethod()]->insert($segments, $route, $pattern);
        }

        self::$matchCache = [];
    }
    public static function match(string $method, string $path): Route|null
    {
        if (!array_key_exists($method, self::$routes)) {
            return null;
        }

        $cacheKey = $method . ':' . $path;
        if (array_key_exists($cacheKey, self::$matchCache)) {
            $cached = self::$matchCache[$cacheKey];

            unset(self::$matchCache[$cacheKey]);
            self::$matchCache[$cacheKey] = $cached;

            if ($cached === false) {
                return null;
            }

            $cached['route']->setMatchedPath($cached['pattern']);
            return $cached['route'];
        }

        $segments = array_values(array_filter(explode('/', $path)));

        if (isset(self::$tries[$method])) {
            $result = self::$tries[$method]->match($segments);

            if ($result['route'] !== null && $result['pattern'] !== null) {
                $route = $result['route'];
                $route->setMatchedPath($result['pattern']);
                self::cacheResult($cacheKey, $route, $result['pattern']);
                return $route;
            }
        }

        for ($i = count($segments); $i > 0; $i--) {
            $current = implode('/', array_slice($segments, 0, $i)) . '/';
            $match = $current . self::WILDCARD_TOKEN;
            if (array_key_exists($match, self::$routes[$method])) {
                $route = self::$routes[$method][$match];
                $route->setMatchedPath($match);
                self::cacheResult($cacheKey, $route, $match);
                return $route;
            }
        }

        $match = self::WILDCARD_TOKEN;
        if (array_key_exists($match, self::$routes[$method])) {
            $route = self::$routes[$method][$match];
            $route->setMatchedPath($match);
            self::cacheResult($cacheKey, $route, $match);
            return $route;
        }

        self::cacheResult($cacheKey, null, null);
        return null;
    }

    protected static function cacheResult(string $cacheKey, ?Route $route, ?string $pattern): void
    {
        if (count(self::$matchCache) >= self::ROUTE_MATCH_CACHE_LIMIT) {
            unset(self::$matchCache[array_key_first(self::$matchCache)]);
        }
        self::$matchCache[$cacheKey] = $route ? ['route' => $route, 'pattern' => $pattern] : false;
    }

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

    public static function reset(): void
    {
        self::$params = [];
        self::$routes = [
            App::REQUEST_METHOD_GET => [],
            App::REQUEST_METHOD_POST => [],
            App::REQUEST_METHOD_PUT => [],
            App::REQUEST_METHOD_PATCH => [],
            App::REQUEST_METHOD_DELETE => [],
        ];
        self::$tries = [];
        self::$matchCache = [];
    }
}
