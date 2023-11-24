<?php

namespace Utopia;

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
        App::REQUEST_METHOD_GET => [],
        App::REQUEST_METHOD_POST => [],
        App::REQUEST_METHOD_PUT => [],
        App::REQUEST_METHOD_PATCH => [],
        App::REQUEST_METHOD_DELETE => [],
    ];

    /**
     * Contains the positions of all params in the paths of all registered Routes.
     *
     * @var array<int>
     */
    protected static array $params = [];

    /**
     * Get all registered routes.
     *
     * @return array
     */
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
     * @param \Utopia\Route $route
     * @return void
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
            $route->setPathParam($key, $index);
        }

        self::$routes[$route->getMethod()][$path] = $route;
    }

    /**
     * Add route to router.
     *
     * @param \Utopia\Route $route
     * @return void
     * @throws \Exception
     */
    public static function addRouteAlias(string $path, Route $route): void
    {
        [$alias] = self::preparePath($path);

        if (array_key_exists($alias, self::$routes[$route->getMethod()]) && !self::$allowOverride) {
            throw new Exception("Route for ({$route->getMethod()}:{$alias}) already registered.");
        }

        self::$routes[$route->getMethod()][$alias] = $route;
    }

    /**
     * Match route against the method and path.
     *
     * @param string $method
     * @param string $path
     * @return \Utopia\Route|null
     */
    public static function match(string $method, string $path): Route|null
    {
        if (!array_key_exists($method, self::$routes)) {
            return null;
        }

        $parts = array_values(array_filter(explode('/', $path)));
        $length = count($parts) - 1;
        $filteredParams = array_filter(self::$params, fn ($i) => $i <= $length);

        foreach (self::combinations($filteredParams) as $sample) {
            $sample = array_filter($sample, fn (int $i) => $i <= $length);
            $match = implode(
                '/',
                array_replace(
                    $parts,
                    array_fill_keys($sample, self::PLACEHOLDER_TOKEN)
                )
            );

            if (array_key_exists($match, self::$routes[$method])) {
                return self::$routes[$method][$match];
            }
        }

        /**
         * Match root wildcard.
         */
        $match = self::WILDCARD_TOKEN;
        if (array_key_exists($match, self::$routes[$method])) {
            return self::$routes[$method][$match];
        }

        /**
         * Match wildcard for path segments.
         */
        foreach ($parts as $part) {
            $current = ($current ?? '') . "{$part}/";
            $match = $current . self::WILDCARD_TOKEN;
            if (array_key_exists($match, self::$routes[$method])) {
                return self::$routes[$method][$match];
            }
        }

        return null;
    }

    /**
     * Get all combinations of the given set.
     *
     * @param array $set
     * @return iterable
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
     * @param string $path
     * @return array
     */
    protected static function preparePath(string $path): array
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
     *
     * @return void
     */
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
    }
}
