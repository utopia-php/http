<?php

namespace Utopia;

class App
{
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
     * Get allow override
     *
     *
     * @return bool
     */
    public static function getAllowOverride(): bool
    {
        return Router::getAllowOverride();
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
        Router::setAllowOverride($value);
    }

    /**
     * If a resource has been created return it, otherwise create it and then return it
     *
     * @param  string  $name
     * @param  bool  $fresh
     * @return mixed
     *
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
     * @param  array  $list
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
     * @param  string  $name
     * @param  callable  $callback
     * @param  array  $injections
     * @return void
     *
     * @throws Exception
     */
    public static function setResource(string $name, callable $callback, array $injections = []): void
    {
        if ($name === 'utopia') {
            throw new Exception("'utopia' is a reserved keyword.", 500);
        }
        self::$resourcesCallbacks[$name] = ['callback' => $callback, 'injections' => $injections, 'reset' => true];
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
    public function execute(Route $route, Request $request, Response $response): static
    {
        $arguments = [];
        $groups = $route->getGroups();
        $pathValues = $route->getPathValues($request);

        try {
            if ($route->getHook()) {
                foreach (self::$init as $hook) { // Global init hooks
                    if (in_array('*', $hook->getGroups())) {
                        $arguments = $this->getArguments($hook, $pathValues, $request->getParams());
                        \call_user_func_array($hook->getAction(), $arguments);
                    }
                }
            }

            foreach ($groups as $group) {
                foreach (self::$init as $hook) { // Group init hooks
                    if (in_array($group, $hook->getGroups())) {
                        $arguments = $this->getArguments($hook, $pathValues, $request->getParams());
                        \call_user_func_array($hook->getAction(), $arguments);
                    }
                }
            }

            if (!($response->isSent())) {
                $arguments = $this->getArguments($route, $pathValues, $request->getParams());

                // Call the action callback with the matched positions as params
                \call_user_func_array($route->getAction(), $arguments);
            }


            foreach ($groups as $group) {
                foreach (self::$shutdown as $hook) { // Group shutdown hooks
                    if (in_array($group, $hook->getGroups())) {
                        $arguments = $this->getArguments($hook, $pathValues, $request->getParams());
                        \call_user_func_array($hook->getAction(), $arguments);
                    }
                }
            }

            if ($route->getHook()) {
                foreach (self::$shutdown as $hook) { // Group shutdown hooks
                    if (in_array('*', $hook->getGroups())) {
                        $arguments = $this->getArguments($hook, $pathValues, $request->getParams());
                        \call_user_func_array($hook->getAction(), $arguments);
                    }
                }
            }
        } catch (\Throwable $e) {
            self::setResource('error', fn () => $e);

            foreach ($groups as $group) {
                foreach (self::$errors as $error) { // Group error hooks
                    if (in_array($group, $error->getGroups())) {
                        try {
                            $arguments = $this->getArguments($error, $pathValues, $request->getParams());
                            \call_user_func_array($error->getAction(), $arguments);
                        } catch (\Throwable $e) {
                            throw new Exception('Error handler had an error: ' . $e->getMessage(), 500, $e);
                        }
                    }
                }
            }

            foreach (self::$errors as $error) { // Global error hooks
                if (in_array('*', $error->getGroups())) {
                    try {
                        $arguments = $this->getArguments($error, $pathValues, $request->getParams());
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
     * @param  Hook  $hook
     * @param  array  $values
     * @param  array  $requestParams
     * @return array
     *
     * @throws Exception
     */
    protected function getArguments(Hook $hook, array $values, array $requestParams): array
    {
        $arguments = [];
        foreach ($hook->getParams() as $key => $param) { // Get value from route or request object
            $existsInRequest = \array_key_exists($key, $requestParams);
            $existsInValues = \array_key_exists($key, $values);
            $paramExists = $existsInRequest || $existsInValues;

            $arg = $existsInRequest ? $requestParams[$key] : $param['default'];
            $value = $existsInValues ? $values[$key] : $arg;

            if (!$param['skipValidation']) {
                if (!$paramExists && !$param['optional']) {
                    throw new Exception('Param "' . $key . '" is not optional.', 400);
                }

                if ($paramExists) {
                    $this->validate($key, $param, $value);
                }
            }

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

        if (self::REQUEST_METHOD_OPTIONS == $method) {
            try {
                foreach ($groups as $group) {
                    foreach (self::$options as $option) { // Group options hooks
                        /** @var Hook $option */
                        if (in_array($group, $option->getGroups())) {
                            \call_user_func_array($option->getAction(), $this->getArguments($option, [], $request->getParams()));
                        }
                    }
                }

                foreach (self::$options as $option) { // Global options hooks
                    /** @var Hook $option */
                    if (in_array('*', $option->getGroups())) {
                        \call_user_func_array($option->getAction(), $this->getArguments($option, [], $request->getParams()));
                    }
                }
            } catch (\Throwable $e) {
                foreach (self::$errors as $error) { // Global error hooks
                    /** @var Hook $error */
                    if (in_array('*', $error->getGroups())) {
                        self::setResource('error', function () use ($e) {
                            return $e;
                        });
                        \call_user_func_array($error->getAction(), $this->getArguments($error, [], $request->getParams()));
                    }
                }
            }

            return $this;
        }

        if (null === $route && null !== self::$wildcardRoute) {
            $route = self::$wildcardRoute;
            $this->route = $route;
            $path = \parse_url($request->getURI(), PHP_URL_PATH);
            $route->path($path);
        }

        if (null !== $route) {
            return $this->execute($route, $request, $response);
        } elseif (self::REQUEST_METHOD_OPTIONS == $method) {
            try {
                foreach ($groups as $group) {
                    foreach (self::$options as $option) { // Group options hooks
                        if (in_array($group, $option->getGroups())) {
                            \call_user_func_array($option->getAction(), $this->getArguments($option, [], $request->getParams()));
                        }
                    }
                }

                foreach (self::$options as $option) { // Global options hooks
                    if (in_array('*', $option->getGroups())) {
                        \call_user_func_array($option->getAction(), $this->getArguments($option, [], $request->getParams()));
                    }
                }
            } catch (\Throwable $e) {
                foreach (self::$errors as $error) { // Global error hooks
                    if (in_array('*', $error->getGroups())) {
                        self::setResource('error', function () use ($e) {
                            return $e;
                        });
                        \call_user_func_array($error->getAction(), $this->getArguments($error, [], $request->getParams()));
                    }
                }
            }
        } else {
            foreach (self::$errors as $error) { // Global error hooks
                if (in_array('*', $error->getGroups())) {
                    self::setResource('error', function () {
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
     * @param  string  $key
     * @param  array  $param
     * @param  mixed  $value
     * @return void
     *
     * @throws Exception
     */
    protected function validate(string $key, array $param, mixed $value): void
    {
        if ($param['optional'] && \is_null($value)) {
            return;
        }

        $validator = $param['validator']; // checking whether the class exists

        if (\is_callable($validator)) {
            $validator = \call_user_func_array($validator, $this->getResources($param['injections']));
        }

        if (!$validator instanceof Validator) { // is the validator object an instance of the Validator class
            throw new Exception('Validator object is not an instance of the Validator class', 500);
        }

        if (!$validator->isValid($value)) {
            throw new Exception('Invalid `' . $key . '` param: ' . $validator->getDescription(), 400);
        }
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
