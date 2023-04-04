<?php

namespace Utopia;

use Exception;

class Router
{
    public const PLACEHOLDER_TOKEN = ':::';

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

    public static function getRoutes(): array
    {
        return self::$routes;
    }

    public static function addRoute(Route $route): void
    {
        [$path, $params] = self::preparePath($route->getPath());

        if (! array_key_exists($route->getMethod(), self::$routes)) {
            throw new Exception("Method ({$route->getMethod()}) not supported.");
        }

        if (array_key_exists($path, self::$routes[$route->getMethod()])) {
            throw new Exception("Route for ({$route->getMethod()}:{$path}) already registered.");
        }

        foreach ($params as $key => $index) {
            $route->setPathParam($key, $index);
        }

        self::$routes[$route->getMethod()][$path] = $route;
    }

    public static function match(string $method, string $path): Route|null
    {
        if (! array_key_exists($method, self::$routes)) {
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

        return null;
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
                if (! in_array($key, self::$params)) {
                    self::$params[] = $key;
                }
            } else {
                $prepare .= $part;
            }
        }

        return [$prepare, $params];
    }
}
