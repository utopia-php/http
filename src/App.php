<?php

namespace Utopia;

use Utopia\Traits\Hooks;
use Utopia\Traits\Resources;

class App
{
    use Resources;
    use Hooks;

    /**
     * Request method constants
     */
    public const REQUEST_METHOD_GET = 'GET';

    public const REQUEST_METHOD_POST = 'POST';

    public const REQUEST_METHOD_PUT = 'PUT';

    public const REQUEST_METHOD_PATCH = 'PATCH';

    public const REQUEST_METHOD_DELETE = 'DELETE';

    public const REQUEST_METHOD_OPTIONS = 'OPTIONS';

    public const REQUEST_METHOD_HEAD = 'HEAD';

    /**
     * Mode Type
     */
    public const MODE_TYPE_DEVELOPMENT = 'development';

    public const MODE_TYPE_STAGE = 'stage';

    public const MODE_TYPE_PRODUCTION = 'production';

    /**
     * Current running mode
     *
     * @var string
     */
    protected static string $mode = '';

    /**
     * Options
     *
     * A callback function for options method requests
     *
     * @var Hook[]
     */
    protected static array $options = [];

    /**
     * Route
     *
     * Memory cached result for chosen route
     *
     * @var Route|null
     */
    protected ?Route $route = null;

    /**
     * Wildcard route
     * If set, this get's executed if no other route is matched
     *
     * @var Route|null
     */
    protected static ?Route $wildcardRoute = null;

    /**
     * App
     *
     * @param  string  $timezone
     */
    public function __construct(string $timezone)
    {
        \date_default_timezone_set($timezone);
    }

    /**
     * GET
     *
     * Add GET request route
     *
     * @param  string  $url
     * @return Route
     */
    public static function get(string $url): Route
    {
        return self::addRoute(self::REQUEST_METHOD_GET, $url);
    }

    /**
     * POST
     *
     * Add POST request route
     *
     * @param  string  $url
     * @return Route
     */
    public static function post(string $url): Route
    {
        return self::addRoute(self::REQUEST_METHOD_POST, $url);
    }

    /**
     * PUT
     *
     * Add PUT request route
     *
     * @param  string  $url
     * @return Route
     */
    public static function put(string $url): Route
    {
        return self::addRoute(self::REQUEST_METHOD_PUT, $url);
    }

    /**
     * PATCH
     *
     * Add PATCH request route
     *
     * @param  string  $url
     * @return Route
     */
    public static function patch(string $url): Route
    {
        return self::addRoute(self::REQUEST_METHOD_PATCH, $url);
    }

    /**
     * DELETE
     *
     * Add DELETE request route
     *
     * @param  string  $url
     * @return Route
     */
    public static function delete(string $url): Route
    {
        return self::addRoute(self::REQUEST_METHOD_DELETE, $url);
    }

    /**
     * Wildcard
     *
     * Add Wildcard route
     *
     * @return Route
     */
    public static function wildcard(): Route
    {
        self::$wildcardRoute = new Route('', '');

        return self::$wildcardRoute;
    }

    /**
     * Options
     *
     * Set a callback function for all request with options method
     *
     * @return Hook
     */
    public static function options(): Hook
    {
        $hook = new Hook();
        $hook->groups(['*']);

        self::$options[] = $hook;

        return $hook;
    }

    /**
     * Get env var
     *
     * Method for querying env varialbles. If $key is not found $default value will be returned.
     *
     * @param  string  $key
     * @param  string|null  $default
     * @return string|null
     */
    public static function getEnv(string $key, string $default = null): ?string
    {
        return $_SERVER[$key] ?? $default;
    }

    /**
     * Get Mode
     *
     * Get current mode
     *
     * @return string
     */
    public static function getMode(): string
    {
        return self::$mode;
    }

    /**
     * Set Mode
     *
     * Set current mode
     *
     * @param  string  $value
     * @return void
     */
    public static function setMode(string $value): void
    {
        self::$mode = $value;
    }

    /**
     * Is app in production mode?
     *
     * @return bool
     */
    public static function isProduction(): bool
    {
        return self::MODE_TYPE_PRODUCTION === self::$mode;
    }

    /**
     * Is app in development mode?
     *
     * @return bool
     */
    public static function isDevelopment(): bool
    {
        return self::MODE_TYPE_DEVELOPMENT === self::$mode;
    }

    /**
     * Is app in stage mode?
     *
     * @return bool
     */
    public static function isStage(): bool
    {
        return self::MODE_TYPE_STAGE === self::$mode;
    }

    /**
     * Get Routes
     *
     * Get all application routes
     *
     * @return array
     */
    public static function getRoutes(): array
    {
        return Router::getRoutes();
    }

    /**
     * Get the current route
     *
     * @return null|Route
     */
    public function getRoute(): ?Route
    {
        return $this->route ?? null;
    }

    /**
     * Set the current route
     *
     * @param  Route  $route
     */
    public function setRoute(Route $route): self
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Add Route
     *
     * Add routing route method, path and callback
     *
     * @param  string  $method
     * @param  string  $url
     * @return Route
     */
    public static function addRoute(string $method, string $url): Route
    {
        $route = new Route($method, $url);

        Router::addRoute($route);

        return $route;
    }

    /**
     * Match
     *
     * Find matching route given current user request
     *
     * @param  Request  $request
     * @param  bool  $fresh If true, will not match any cached route
     * @return null|Route
     */
    public function match(Request $request, bool $fresh = false): ?Route
    {
        if (null !== $this->route && !$fresh) {
            return $this->route;
        }

        $url = \parse_url($request->getURI(), PHP_URL_PATH);
        $method = $request->getMethod();
        $method = (self::REQUEST_METHOD_HEAD == $method) ? self::REQUEST_METHOD_GET : $method;

        $this->route = Router::match($method, $url);

        return $this->route;
    }

    /**
     * Execute a given route with middlewares and error handling
     *
     * @param  Route  $route
     * @param  Request  $request
     */
    public function execute(Route $route, Request $request): static
    {
        $arguments = [];
        $groups = $route->getGroups();
        $pathValues = $route->getPathValues($request);

        try {
            if ($route->getHook()) {
                $this->callHooksByGroup(self::$init, '*', $pathValues, $request->getParams());
            }

            foreach ($groups as $group) {
                $this->callHooksByGroup(self::$init, $group, $pathValues, $request->getParams());
            }

            /**
             * Call the route action.
             */
            $this->callHook($route, $pathValues, $request->getParams());

            foreach ($groups as $group) {
                $this->callHooksByGroup(self::$shutdown, $group, $pathValues, $request->getParams());
            }

            if ($route->getHook()) {
                $this->callHooksByGroup(self::$shutdown, '*', $pathValues, $request->getParams());
            }
        } catch (\Throwable $e) {
            self::setResource('error', fn () => $e);

            try {
                foreach ($groups as $group) {
                    $this->callHooksByGroup(self::$errors, $group, $pathValues, $request->getParams());
                }
                $this->callHooksByGroup(self::$errors, '*', $pathValues, $request->getParams());
            } catch (\Throwable $e) {
                throw new Exception('Error handler had an error: ' . $e->getMessage(), 500, $e);
            }
        }

        return $this;
    }

    /**
     * Run
     *
     * This is the place to initialize any pre routing logic.
     * This is where you might want to parse the application current URL by any desired logic
     *
     * @param  Request  $request
     * @param  Response  $response
     */
    public function run(Request $request, Response $response): static
    {
        $this->resources['request'] = $request;
        $this->resources['response'] = $response;

        self::setResource('request', function () use ($request) {
            return $request;
        });

        self::setResource('response', function () use ($response) {
            return $response;
        });

        $method = $request->getMethod();
        $route = $this->match($request);
        $groups = ($route instanceof Route) ? $route->getGroups() : [];

        if (self::REQUEST_METHOD_HEAD == $method) {
            $method = self::REQUEST_METHOD_GET;
            $response->disablePayload();
        }

        if (null === $route && null !== self::$wildcardRoute) {
            $route = self::$wildcardRoute;
            $path = \parse_url($request->getURI(), PHP_URL_PATH);
            $route->path($path);
        }

        if (null !== $route) {
            return $this->execute($route, $request);
        } elseif (self::REQUEST_METHOD_OPTIONS === $method) {
            try {
                foreach ($groups as $group) {
                    $this->callHooksByGroup(self::$options, $group, params: $request->getParams());
                }
                $this->callHooksByGroup(self::$options, '*', params: $request->getParams());
            } catch (\Throwable $e) {
                self::setResource('error', fn () => $e);
                $this->callHooksByGroup(self::$errors, '*', params: $request->getParams());
            }
        } else {
            self::setResource('error', fn () => new Exception('Not Found', 404));
            $this->callHooksByGroup(self::$errors, '*', params: $request->getParams());
        }

        return $this;
    }

    /**
     * Reset all the static variables
     *
     * @return void
     */
    public static function reset(): void
    {
        Router::reset();
        self::$resourcesCallbacks = [];
        self::$mode = '';
        self::$errors = [];
        self::$init = [];
        self::$shutdown = [];
        self::$options = [];
    }
}
