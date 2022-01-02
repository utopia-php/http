<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Core
 *
 * @link https://github.com/utopia-php/framework
 * @author Appwrite Team <team@appwrite.io>
 */

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
     * @var array
     */
    protected static array $errors = [
        '*' => [],
    ];

    /**
     * Init
     *
     * A callback function that is initialized on application start
     *
     * @var array
     */
    protected static array $init = [
        '*' => [],
    ];

    /**
     * Shutdown
     *
     * A callback function that is initialized on application end
     *
     * @var array
     */
    protected static array $shutdown = [
        '*' => [],
    ];

    /**
     * Options
     *
     * A callback function for options method requests
     *
     * @var array
     */
    protected static array $options = [
        '*' => [],
    ];

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
     * Init
     *
     * Set a callback function that will be initialized on application start
     *
     * @param callable $callback
     * @param array $resources
     * @param string $group Pass "*" for all
     *
     * @return void
     */
    public static function init(callable $callback, array $resources = [], string $group = '*'): void
    {
        if (!isset(self::$init[$group])) {
            self::$init[$group] = [];
        }

        self::$init[$group][] = ['callback' => $callback, 'resources' => $resources];
    }

    /**
     * Shutdown
     *
     * Set a callback function that will be initialized on application end
     *
     * @param callable $callback
     * @param array $resources
     * @param string $group Use "*" for all
     *
     * @return void
     */
    public static function shutdown(callable $callback, array $resources = [], string $group = '*'): void
    {
        if (!isset(self::$shutdown[$group])) {
            self::$shutdown[$group] = [];
        }

        self::$shutdown[$group][] = ['callback' => $callback, 'resources' => $resources];
    }

    /**
     * Options
     *
     * Set a callback function for all request with options method
     *
     * @param callable $callback
     * @param array $resources
     * @param string $group Use "*" for all
     *
     * @return void
     */
    public static function options(callable $callback, array $resources = [], string $group = '*'): void
    {
        if (!isset(self::$options[$group])) {
            self::$options[$group] = [];
        }

        self::$options[$group][] = ['callback' => $callback, 'resources' => $resources];
    }

    /**
     * Error
     *
     * An error callback for failed or no matched requests
     *
     * @param callable $callback
     * @param array $resources
     * @param string $group Use "*" for all
     *
     * @return void
     */
    public static function error(callable $callback, array $resources = [], string $group = '*'): void
    {
        if (!isset(self::$errors[$group])) {
            self::$errors[$group] = [];
        }

        self::$errors[$group][] = ['callback' => $callback, 'resources' => $resources];
    }

    /**
     * Get env var
     *
     * Method for querying env varialbles. If $key is not found $default value will be returned.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function getEnv(string $key, mixed $default = null): mixed
    {
        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
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

            $this->resources[$name] = \call_user_func_array(self::$resourcesCallbacks[$name]['callback'],
                $this->getResources(self::$resourcesCallbacks[$name]['injections']));
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
     *
     * @return self
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
     * @param string $method
     * @param string $url
     * @return Route
     */
    protected static function addRoute(string $method, string $url): Route
    {
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
     * @return null|Route
     */
    public function match(Request $request): ?Route
    {
        if (null !== $this->route) {
            return $this->route;
        }

        $url    = \parse_url($request->getURI(), PHP_URL_PATH);
        $method = $request->getMethod();
        $method = (self::REQUEST_METHOD_HEAD == $method) ? self::REQUEST_METHOD_GET : $method;

        if (!isset(self::$routes[$method])) {
            self::$routes[$method] = [];
        }

        foreach (self::$routes[$method] as $routeUrl => $route) {
            /* @var $route Route */

            // convert urls like '/users/:uid/posts/:pid' to regular expression
            $regex = '@' . \preg_replace('@:[^/]+@', '([^/]+)', $routeUrl) . '@';

            // Check if the current request matches the expression
            if (!\preg_match($regex, $url, $this->matches)) {
                continue;
            }

            \array_shift($this->matches);
            $this->route = $route;

            if($routeUrl == $route->getAliasPath()) {
                $this->route->setIsAlias(true);
            } else {
                $this->route->setIsAlias(false);
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
     * @return self
     */
    public function execute(Route $route, Request $request): self
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
            if ($route->getMiddleware()) {
                foreach (self::$init['*'] as $init) { // Global init hooks
                    \call_user_func_array($init['callback'], $this->getResources($init['resources']));
                }
            }

            foreach ($groups as $group) {
                if (isset(self::$init[$group])) {
                    foreach (self::$init[$group] as $init) { // Group init hooks
                        \call_user_func_array($init['callback'], $this->getResources($init['resources']));
                    }
                }
            }

            $args = $request->getParams();

            foreach ($route->getParams() as $key => $param) { // Get value from route or request object
                $arg = (isset($args[$key])) ? $args[$key] : $param['default'];
                $value = (isset($values[$key])) ? $values[$key] : $arg;

                if($route->getIsAlias() && isset($route->getAliasParams()[$key])) {
                    $value = $route->getAliasParams()[$key];
                }

                $value = ($value === '' || is_null($value)) ? $param['default'] : $value;

                $this->validate($key, $param, $value);
                $arguments[$param['order']] = $value;
            }

            foreach ($route->getInjections() as $key => $injection) {
                $arguments[$injection['order']] = $this->getResource($injection['name']);
            }

            // Call the callback with the matched positions as params
            \call_user_func_array($route->getAction(), $arguments);

            foreach ($groups as $group) {
                if (isset(self::$shutdown[$group])) {
                    foreach (self::$shutdown[$group] as $shutdown) { // Group shutdown hooks
                        \call_user_func_array($shutdown['callback'], $this->getResources($shutdown['resources']));
                    }
                }
            }

            if ($route->getMiddleware()) {
                foreach (self::$shutdown['*'] as $shutdown) { // Global shutdown hooks
                    \call_user_func_array($shutdown['callback'], $this->getResources($shutdown['resources']));
                }
            }
        } catch (\Throwable $e) {
            foreach ($groups as $group) {
                if (isset(self::$errors[$group])) {
                    foreach (self::$errors[$group] as $error) { // Group error hooks
                        self::setResource('error', function() use ($e) {
                            return $e;
                        });
                        \call_user_func_array($error['callback'], $this->getResources($error['resources']));
                    }
                }
            }

            foreach (self::$errors['*'] as $error) { // Global error hooks
                self::setResource('error', function() use ($e) {
                    return $e;
                });
                \call_user_func_array($error['callback'], $this->getResources($error['resources']));
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
     * @param Request $request
     * @param Response $response
     * @return self
     */
    public function run(Request $request, Response $response): self
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
                    if($route->getAliasPath()) {
                        self::$routes[$method][$route->getAliasPath()] = $route;
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

        if (null !== $route) {
            return $this->execute($route, $request);
        } elseif (self::REQUEST_METHOD_OPTIONS == $method) {
            try {
                foreach ($groups as $group) {
                    if (isset(self::$options[$group])) {
                        foreach (self::$options[$group] as $option) { // Group options hooks
                            \call_user_func_array($option['callback'], $this->getResources($option['resources']));
                        }
                    }
                }

                foreach (self::$options['*'] as $option) { // Global options hooks
                    \call_user_func_array($option['callback'], $this->getResources($option['resources']));
                }
            } catch (\Throwable $e) {
                foreach (self::$errors['*'] as $error) { // Global error hooks
                    self::setResource('error', function() use ($e) {
                        return $e;
                    });
                    \call_user_func_array($error['callback'], $this->getResources($error['resources']));
                }
            }
        } else {
            foreach (self::$errors['*'] as $error) { // Global error hooks
                self::setResource('error', function() {
                    return new Exception('Not Found', 404);
                });
                \call_user_func_array($error['callback'], $this->getResources($error['resources']));
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
                throw new Exception('Invalid ' .$key . ': ' . $validator->getDescription(), 400);
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
        self::$errors = [
            '*' => [],
        ];
        self::$init = [
            '*' => [],
        ];
        self::$shutdown = [
            '*' => [],
        ];
        self::$options = [
            '*' => [],
        ];
        self::$sorted = false;
    }

}
