<?php

namespace Utopia;

class App
{
    /**
     * Request method constants
     */
    const REQUEST_METHOD_GET        = 'GET';
    const REQUEST_METHOD_POST       = 'POST';
    const REQUEST_METHOD_PUT        = 'PUT';
    const REQUEST_METHOD_PATCH      = 'PATCH';
    const REQUEST_METHOD_DELETE     = 'DELETE';
    const REQUEST_METHOD_OPTIONS    = 'OPTIONS';
    const REQUEST_METHOD_HEAD       = 'HEAD';

    /**
     * Mode Type
     */
    const MODE_TYPE_DEVELOPMENT  = 'development';
    const MODE_TYPE_STAGE        = 'stage';
    const MODE_TYPE_PRODUCTION   = 'production';

    /**
     * Routes
     *
     * @var array
     */
    protected static array $routes = [
        self::REQUEST_METHOD_GET       => [],
        self::REQUEST_METHOD_POST      => [],
        self::REQUEST_METHOD_PUT       => [],
        self::REQUEST_METHOD_PATCH     => [],
        self::REQUEST_METHOD_DELETE    => [],
    ];

    /**
     * @var array
     */
    protected array $resources = [
        'error' => null,
    ];

    /**
     * @var array
     */
    protected static array $resourcesCallbacks = [];

    /**
     * Current running mode
     *
     * @var string
     */
    protected static string $mode = '';

    /**
     * Errors
     *
     * Errors callbacks
     *
     * @var Hook[]
     */
    protected static array $errors = [];

    /**
     * Init
     *
     * A callback function that is initialized on application start
     *
     * @var Hook[]
     */
    protected static array $init = [];

    /**
     * Shutdown
     *
     * A callback function that is initialized on application end
     *
     * @var Hook[]
     */
    protected static array $shutdown = [];

    /**
     * Options
     *
     * A callback function for options method requests
     *
     * @var Hook[]
     */
    protected static array $options = [];

    /**
     * Is Sorted?
     *
     * @var bool
     */
    protected static bool $sorted = false;

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
     * Matches
     *
     * Parameters matched from URL regex
     *
     * @var array
     */
    protected array $matches = [];

    /**
     * App
     *
     * @param string $timezone
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
     * @param string $url
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
     * @param string $url
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
     * @param string $url
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
     * @param string $url
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
     * @param string $url
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
     * Init
     *
     * Set a callback function that will be initialized on application start
     *
     * @return Hook
     */
    public static function init(): Hook
    {
        $hook = new Hook();
        $hook->groups(['*']);
        self::$init[] = $hook;
        return $hook;
    }

    /**
     * Shutdown
     *
     * Set a callback function that will be initialized on application end
     *
     * @return Hook
     */
    public static function shutdown(): Hook
    {
        $hook = new Hook();
        $hook->groups(['*']);
        self::$shutdown[] = $hook;
        return $hook;
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
     * Error
     *
     * An error callback for failed or no matched requests
     *
     * @return Hook
     */
    public static function error(): Hook
    {
        $hook = new Hook();
        $hook->groups(['*']);
        self::$errors[] = $hook;
        return $hook;
    }

    /**
     * Get env var
     *
     * Method for querying env varialbles. If $key is not found $default value will be returned.
     *
     * @param string $key
     * @param string|null $default
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
     * @param string $value
     *
     * @return void
     */
    public static function setMode(string $value): void
    {
        self::$mode = $value;
    }

    /**
     * If a resource has been created return it, otherwise create it and then return it
     *
     * @param string $name
     * @param bool $fresh
     * @return mixed
     * @throws Exception
     */
    public function getResource(string $name, bool $fresh = false): mixed
    {
        if ($name === 'utopia') {
            return $this;
        }

        if (!\array_key_exists($name, $this->resources) || $fresh || self::$resourcesCallbacks[$name]['reset']) {
            if (!\array_key_exists($name, self::$resourcesCallbacks)) {
                throw new Exception('Failed to find resource: "' . $name . '"');
            }

            $this->resources[$name] = \call_user_func_array(
                self::$resourcesCallbacks[$name]['callback'],
                $this->getResources(self::$resourcesCallbacks[$name]['injections'])
            );
        }

        self::$resourcesCallbacks[$name]['reset'] = false;

        return $this->resources[$name];
    }

    /**
     * Get Resources By List
     *
     * @param array $list
     * @return array
     */
    public function getResources(array $list): array
    {
        $resources = [];

        foreach ($list as $name) {
            $resources[$name] = $this->getResource($name);
        }

        return $resources;
    }

    /**
     * Set a new resource callback
     *
     * @param string $name
     * @param callable $callback
     * @param array $injections
     *
     * @throws Exception
     *
     * @return void
     */
    public static function setResource(string $name, callable $callback, array $injections = []): void
    {
        if ($name === 'utopia') {
            throw new Exception("'utopia' is a reserved keyword.", 500);
        }
        self::$resourcesCallbacks[$name] = ['callback' => $callback, 'injections' => $injections, 'reset' => true];
    }

    /**
     */
    /**
     * Is app in production mode?
     *
     * @return bool
     */
    public static function isProduction(): bool
    {
        return (self::MODE_TYPE_PRODUCTION === self::$mode);
    }

    /**
     * Is app in development mode?
     *
     * @return bool
     */
    public static function isDevelopment(): bool
    {
        return (self::MODE_TYPE_DEVELOPMENT === self::$mode);
    }

    /**
     * Is app in stage mode?
     *
     * @return bool
     */
    public static function isStage(): bool
    {
        return (self::MODE_TYPE_STAGE === self::$mode);
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
        return self::$routes;
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
     * @param Route $route
     */
    public function setRoute(Route $route): static
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Add Route
     *
     * Add routing route method, path and callback
     *
     * @param string $method
     * @param string $url
     * @return Route
     */
    public static function addRoute(string $method, string $url): Route
    {
        if (!array_key_exists($method, self::$routes)) {
            throw new Exception("Invalid Request Method");
        }
        $route = new Route($method, $url);

        self::$routes[$method][$url] = $route;

        self::$sorted = false;

        return $route;
    }

    /**
     * Match
     *
     * Find matching route given current user request
     *
     * @param Request $request
     * @param bool $fresh If true, will not match any cached route
     * @return null|Route
     */
    public function match(Request $request, bool $fresh = false): ?Route
    {
        if (null !== $this->route && !$fresh) {
            return $this->route;
        }

        $url    = \parse_url($request->getURI(), PHP_URL_PATH);
        $method = $request->getMethod();
        $method = (self::REQUEST_METHOD_HEAD == $method) ? self::REQUEST_METHOD_GET : $method;

        if (!isset(self::$routes[$method])) {
            self::$routes[$method] = [];
        }

        foreach (self::$routes[$method] as $routeUrl => $route) {
            /** @var Route $route */

            // convert urls like '/users/:uid/posts/:pid' to regular expression
            $regex = '@' . \preg_replace('@:[^/]+@', '([^/]+)', $routeUrl) . '@';

            // Check if the current request matches the expression
            if (!\preg_match($regex, $url, $this->matches)) {
                continue;
            }

            \array_shift($this->matches);
            $this->route = $route;

            if (isset($route->getAliases()[$routeUrl])) {
                $this->route->setAliasPath($routeUrl);
            } else {
                $this->route->setAliasPath(null);
            }

            break;
        }

        if (!empty($this->route) && ('/' === $this->route->getPath()) && ($url != $this->route->getPath())) {
            return null;
        }

        return $this->route;
    }

    /**
     * Execute a given route with middlewares and error handling
     *
     * @param Route $route
     * @param Request $request
     */
    public function execute(Route $route, Request $request): static
    {
        $keys       = [];
        $arguments  = [];
        $groups     = $route->getGroups();

        // Extract keys from URL
        $url = $route->getIsAlias() ? $route->getAliasPath() : $route->getPath();
        $keyRegex = '@^' . \preg_replace('@:[^/]+@', ':([^/]+)', $url) . '$@';
        \preg_match($keyRegex, $url, $keys);

        // Remove the first key and value ( corresponding to full regex match )
        \array_shift($keys);

        // combine keys and values to one array
        $values = \array_combine($keys, $this->matches);
        try {

            if ($route->getHook()) {
                foreach (self::$init as $hook) { // Global init hooks
                    if(in_array('*', $hook->getGroups())) {
                        $arguments = $this->getArguments($hook, $values, $request->getParams());
                        \call_user_func_array($hook->getAction(), $arguments);
                    }
                }
            }

            foreach ($groups as $group) {
                foreach (self::$init as $hook) { // Group init hooks
                    if(in_array($group, $hook->getGroups())) {
                        $arguments = $this->getArguments($hook, $values, $request->getParams());
                        \call_user_func_array($hook->getAction(), $arguments);
                    }
                }
            }

            $arguments = $this->getArguments($route, $values, $request->getParams());

            // Call the callback with the matched positions as params
            if($route->getIsActive()){
                \call_user_func_array($route->getAction(), $arguments);
            }

            $route->setIsActive(true);

            foreach ($groups as $group) {
                foreach (self::$shutdown as $hook) { // Group shutdown hooks
                    /** @var Hook $hook */
                    if(in_array($group, $hook->getGroups())) {
                        $arguments = $this->getArguments($hook, $values, $request->getParams());
                        \call_user_func_array($hook->getAction(), $arguments);
                    }
                }
            }

            if ($route->getHook()) {
                foreach (self::$shutdown as $hook) { // Group shutdown hooks
                    /** @var Hook $hook */
                    if(in_array('*', $hook->getGroups())) {
                        $arguments = $this->getArguments($hook, $values, $request->getParams());
                        \call_user_func_array($hook->getAction(), $arguments);
                    }
                }
            }
        } catch (\Throwable $e) {
            foreach ($groups as $group) {
                foreach (self::$errors as $error) { // Group error hooks
                    /** @var Hook $error */
                    if(in_array($group, $error->getGroups())) {
                        self::setResource('error', fn () => $e);
                        try {
                            $arguments = $this->getArguments($error, $values, $request->getParams());
                            \call_user_func_array($error->getAction(), $arguments);
                        } catch (\Throwable $e) {
                            throw new Exception('Error handler had an error: ' . $e->getMessage(), 500, $e);
                        }
                    }
                }
            }

            foreach (self::$errors as $error) { // Global error hooks
                /** @var Hook $error */
                if(in_array('*', $error->getGroups())) {
                    self::setResource('error', fn() => $e);
                    try {
                        $arguments = $this->getArguments($error, $values, $request->getParams());
                        \call_user_func_array($error->getAction(), $arguments);
                    } catch (\Throwable $e) {
                        throw new Exception('Error handler had an error: ' . $e->getMessage(), 500, $e);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Get Arguments
     *
     * @param Hook $hook
     * @param array $values
     * @param array $requestParams
     * @return array
     * @throws Exception
     */
    protected function getArguments(Hook $hook, array $values, array $requestParams): array
    {
        $arguments = [];
        foreach ($hook->getParams() as $key => $param) { // Get value from route or request object
            $arg = (isset($requestParams[$key])) ? $requestParams[$key] : $param['default'];
            $value = (isset($values[$key])) ? $values[$key] : $arg;

            if ($hook instanceof Route) {
                if ($hook->getIsAlias() && isset($hook->getAliasParams($hook->getAliasPath())[$key])) {
                    $value = $hook->getAliasParams($hook->getAliasPath())[$key];
                }
            }

            $value = ($value === '' || is_null($value)) ? $param['default'] : $value;

            $this->validate($key, $param, $value);
            $hook->setParamValue($key, $value);
            $arguments[$param['order']] = $value;
        }

        foreach ($hook->getInjections() as $key => $injection) {
            $arguments[$injection['order']] = $this->getResource($injection['name']);
        }

        return $arguments;
    }

    /**
     * Run
     *
     * This is the place to initialize any pre routing logic.
     * This is where you might want to parse the application current URL by any desired logic
     *
     * @param Request $request
     * @param Response $response
     */
    public function run(Request $request, Response $response): static
    {
        self::setResource('request', function() use ($request) {
            return $request;
        });

        self::setResource('response', function() use ($response) {
            return $response;
        });

        /*
         * Re-order array
         *
         * For route to work with similar links where one is shorter than other
         *  but both might match given pattern
         */
        if (!self::$sorted) {
            foreach (self::$routes as $method => $list) { //adding route alias in $routes
                foreach ($list as $key => $route) {
                    /** @var Route $route */
                    foreach (array_keys($route->getAliases()) as $path) {
                        self::$routes[$method][$path] = $route;
                    }
                }
            }
            foreach (self::$routes as $method => $list) {
                \uksort(self::$routes[$method], function (string $a, string $b) {
                    return \strlen($b) - \strlen($a);
                });

                \uksort(self::$routes[$method], function (string $a, string $b) {
                    $result = \count(\explode('/', $b)) - \count(\explode('/', $a));

                    if($result === 0) {
                        return \substr_count($a, ':') - \substr_count($b, ':');
                    }

                    return $result;
                });
            }

            self::$sorted = true;
        }

        $method     = $request->getMethod();
        $route      = $this->match($request);
        $groups     = ($route instanceof Route) ? $route->getGroups() : [];

        if (self::REQUEST_METHOD_HEAD == $method) {
            $method = self::REQUEST_METHOD_GET;
            $response->disablePayload();
        }

        if(null === $route && null !== self::$wildcardRoute) {
            $route = self::$wildcardRoute;
            $path = \parse_url($request->getURI(), PHP_URL_PATH);
            $route->path($path);
        }

        if (null !== $route) {
            return $this->execute($route, $request);
        } elseif (self::REQUEST_METHOD_OPTIONS == $method) {
            try {
                foreach ($groups as $group) {
                    foreach (self::$options as $option) { // Group options hooks
                        /** @var Hook $option */
                        if(in_array($group, $option->getGroups())) {
                            \call_user_func_array($option->getAction(), $this->getArguments($option, [], $request->getParams()));
                        }
                    }
                }

                foreach (self::$options as $option) { // Global options hooks
                    /** @var Hook $option */
                    if(in_array('*', $option->getGroups())) {
                        \call_user_func_array($option->getAction(), $this->getArguments($option, [], $request->getParams()));
                    }
                }
            } catch (\Throwable $e) {
                foreach (self::$errors as $error) { // Global error hooks
                    /** @var Hook $error */
                    if(in_array('*', $error->getGroups())) {
                        self::setResource('error', function() use ($e) {
                            return $e;
                        });
                        \call_user_func_array($error->getAction(), $this->getArguments($error, [], $request->getParams()));
                    }
                }
            }
        } else {
            foreach (self::$errors as $error) { // Global error hooks
                if(in_array('*', $error->getGroups())) {
                    self::setResource('error', function() {
                        return new Exception('Not Found', 404);
                    });
                    \call_user_func_array($error->getAction(), $this->getArguments($error, [], $request->getParams()));
                }
            }
        }

        return $this;
    }

    /**
     * Validate Param
     *
     * Creates an validator instance and validate given value with given rules.
     *
     * @param string $key
     * @param array $param
     * @param mixed $value
     * @param array $resources
     *
     * @throws Exception
     *
     * @return void
     */
    protected function validate(string $key, array $param, mixed $value): void
    {
        if ('' !== $value && !is_null($value)) {
            $validator = $param['validator']; // checking whether the class exists

            if (\is_callable($validator)) {
                $validator = \call_user_func_array($validator, $this->getResources($param['injections']));
            }

            if (!$validator instanceof Validator) { // is the validator object an instance of the Validator class
                throw new Exception('Validator object is not an instance of the Validator class', 500);
            }
            if (!$validator->isValid($value)) {
                throw new Exception('Invalid ' . $key . ': ' . $validator->getDescription(), 400);
            }
        } elseif (!$param['optional']) {
            throw new Exception('Param "' . $key . '" is not optional.', 400);
        }
    }

    /**
     * Reset all the static variables
     */
    public static function reset(): void
    {
        self::$resourcesCallbacks = [];
        self::$mode = '';
        self::$errors = [];
        self::$init = [];
        self::$shutdown = [];
        self::$options = [];
        self::$sorted = false;
        self::$routes = [
            self::REQUEST_METHOD_GET       => [],
            self::REQUEST_METHOD_POST      => [],
            self::REQUEST_METHOD_PUT       => [],
            self::REQUEST_METHOD_PATCH     => [],
            self::REQUEST_METHOD_DELETE    => [],
        ];
    }

}
