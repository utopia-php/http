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
    protected static $routes = [
        self::REQUEST_METHOD_GET       => [],
        self::REQUEST_METHOD_POST      => [],
        self::REQUEST_METHOD_PUT       => [],
        self::REQUEST_METHOD_PATCH     => [],
        self::REQUEST_METHOD_DELETE    => [],
    ];

    /**
     * @var array
     */
    protected $resources = [
        'error' => null,
    ];

    /**
     * @var array
     */
    protected static $resourcesCallbacks = [];

    /**
     * Current running mode
     *
     * @var string
     */
    protected static $mode = '';

    /**
     * Errors
     *
     * Errors callbacks
     *
     * @var callback
     */
    protected static $errors = [
        '*' => [],
    ];

    /**
     * Init
     *
     * A callback function that is initialized on application start
     *
     * @var callback[]
     */
    protected static $init = [
        '*' => [],
    ];

    /**
     * Shutdown
     *
     * A callback function that is initialized on application end
     *
     * @var callback[]
     */
    protected static $shutdown = [
        '*' => [],
    ];

    /**
     * Options
     *
     * A callback function for options method requests
     *
     * @var callback[]
     */
    protected static $options = [
        '*' => [],
    ];

    /**
     * Is Sorted?
     *
     * @var bool
     */
    protected static $sorted = false;

    /**
     * Route
     *
     * Memory cached result for chosen route
     *
     * @var null
     */
    protected $route = null;

    /**
     * Matches
     *
     * Parameters matched from URL regex
     *
     * @var array
     */
    protected $matches = [];

    /**
     * App
     *
     * @param string $timezone
     * @param bool $mode Current mode
     */
    public function __construct($timezone)
    {
        $this->resources['utopia'] = $this;
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
    public static function get($url): Route
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
    public static function post($url): Route
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
    public static function put($url): Route
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
    public static function patch($url): Route
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
    public static function delete($url): Route
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
     * @return $this
     */
    public static function init(callable $callback, array $resources = [], string $group = '*')
    {
        if(!isset(self::$init[$group])) {
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
     * @return $this
     */
    public static function shutdown(callable $callback, array $resources = [], string $group = '*')
    {
        if(!isset(self::$shutdown[$group])) {
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
     * @return $this
     */
    public static function options(callable $callback, array $resources = [], string $group = '*')
    {
        if(!isset(self::$options[$group])) {
            self::$options[$group] = [];
        }

        self::$options[$group][] = ['callback' => $callback, 'resources' => $resources];
    }

    /**
     * Error
     *
     * An error callback for failed or no matched requests
     *
     * @param callbale $callback
     * @param array $resources
     * @param string $group Use "*" for all
     * @return $this
     */
    public static function error(callable $callback, array $resources = [], string $group = '*')
    {
        if(!isset(self::$errors[$group])) {
            self::$errors[$group] = [];
        }

        self::$errors[$group][] = ['callback' => $callback, 'resources' => $resources];
    }

    /**
     * Get env var
     *
     * Method for querying env varialbles. If $key is not found $default value will be returned.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function getEnv($key, $default = null)
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
     * @return string
     */
    public static function setMode($value): void
    {
        self::$mode = $value;
    }

    /**
     * If a resource has been created returns it, otherwise create and than return it
     *
     * @param string $name
     * @param bool $fresh
     * @return mixed
     * @throws Exception
     */
    public function getResource(string $name, $fresh = false)
    {
        if(!\array_key_exists($name, $this->resources) || $fresh) {
            if(!\array_key_exists($name, self::$resourcesCallbacks)) {
                throw new Exception('Failed to find resource: "' . $name . '"');
            }

            $this->resources[$name] = \call_user_func_array(self::$resourcesCallbacks[$name]['callback'],
                $this->getResources(self::$resourcesCallbacks[$name]['injections']));
        }

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
     * @throws Exception
     */
    public static function setResource(string $name, callable $callback, array $injections = [])
    {
        if(\array_key_exists($name, self::$resourcesCallbacks)) {
            throw new Exception('Callback with the name "' . $name . '" already exists');
        }

        self::$resourcesCallbacks[$name] = ['callback' => $callback, 'injections' => $injections];
    }

    /**
     * Is app in production mode?
     */
    public static function isProduction(): bool
    {
        return (self::MODE_TYPE_PRODUCTION === self::$mode);
    }

    /**
     * Is app in development mode?
     */
    public static function isDevelopment(): bool
    {
        return (self::MODE_TYPE_DEVELOPMENT === self::$mode);
    }

    /**
     * Is app in stage mode?
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
     * Add Route
     *
     * Add routing route method, path and callback
     *
     * @param string $method
     * @param string $url
     * @return Route
     */
    protected static function addRoute($method, $url): Route
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
    public function match(Request $request)
    {
        if (null !== $this->route) {
            return $this->route;
        }

        $url    = \parse_url($request->getServer('REQUEST_URI', ''), PHP_URL_PATH);
        $method = $request->getServer('REQUEST_METHOD', '');
        $method = (self::REQUEST_METHOD_HEAD == $method) ? self::REQUEST_METHOD_GET : $method;

        if (!isset(self::$routes[$method])) {
            self::$routes[$method] = [];
        }

        foreach (self::$routes[$method] as  $route) {
            /* @var $route Route */

            // convert urls like '/users/:uid/posts/:pid' to regular expression
            $regex = '@' . \preg_replace('@:[^/]+@', '([^/]+)', $route->getURL()) . '@';

            // Check if the current request matches the expression
            if (!\preg_match($regex, $url, $this->matches)) {
                continue;
            }

            \array_shift($this->matches);
            $this->route = $route;
            break;
        }

        if (!empty($this->route) && ('/' === $this->route->getURL()) && ($url != $this->route->getURL())) {
            return null;
        }

        return $this->route;
    }

    /**
     * Execute a given route with middlewares and error handling
     * 
     * @param Route $route
     * @return self
     */
    public function execute(Route $route, array $args = []): self
    {
        $keys       = [];
        $params     = [];
        $groups     = ($route instanceof Route) ? $route->getGroups() : [];

        // Extract keys from URL
        $keyRegex = '@^' . \preg_replace('@:[^/]+@', ':([^/]+)', $route->getURL()) . '$@';
        \preg_match($keyRegex, $route->getURL(), $keys);

        // Remove the first key and value ( corresponding to full regex match )
        \array_shift($keys);

        // combine keys and values to one array
        $values = \array_combine($keys, $this->matches);

        try {
            foreach (self::$init['*'] as $init) { // Global init hooks
                \call_user_func_array($init['callback'], $this->getResources($init['resources']));
            }

            foreach ($groups as $group) {
                if(isset(self::$init[$group])) {
                    foreach (self::$init[$group] as $init) { // Group init hooks
                        \call_user_func_array($init['callback'], $this->getResources($init['resources']));
                    }
                }
            }

            foreach ($route->getParams() as $key => $param) {
                // Get value from route or request object
                $arg = (isset($args[$key])) ? $args[$key] : $param['default'];
                $value = isset($values[$key]) ? $values[$key] : $arg;
                $value = ($value === '') ? $param['default'] : $value;

                $this->validate($key, $param, $value);

                $params[$key] = $value;
            }

            // Call the callback with the matched positions as params
            \call_user_func_array($route->getAction(), array_merge($params, $this->getResources($route->getResources())));
            
            foreach ($groups as $group) {
                if(isset(self::$shutdown[$group])) {
                    foreach (self::$shutdown[$group] as $shutdown) { // Group shutdown hooks
                        \call_user_func_array($shutdown['callback'], $this->getResources($shutdown['resources']));
                    }
                }
            }

            foreach (self::$shutdown['*'] as $shutdown) { // Global shutdown hooks
                \call_user_func_array($shutdown['callback'], $this->getResources($shutdown['resources']));
            }
        } catch (\Throwable $e) {
            foreach ($groups as $group) {
                if(isset(self::$errors[$group])) {
                    foreach (self::$errors[$group] as $error) { // Group shutdown hooks
                        $this->resources['error'] = $e;
                        \call_user_func_array($error['callback'], $this->getResources($error['resources']));
                    }
                }
            }

            foreach (self::$errors['*'] as $error) { // Global error hooks
                $this->resources['error'] = $e;
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
        $this->resources['request'] = $request;
        $this->resources['response'] = $response;
        
        /*
         * Re-order array
         *
         * For route to work with similar links where one is shorter than other
         *  but both might match given pattern
         */
        if(!self::$sorted) {
            foreach (self::$routes as $method => $list) {
                \uksort(self::$routes[$method], function ($a, $b) {
                    return \strlen($b) - \strlen($a);
                });
                
                \uksort(self::$routes[$method], function ($a, $b) {
                    return \count(\explode('/', $b)) - \count(\explode('/', $a));
                });
            }

            self::$sorted = true;
        }
        
        $method     = $request->getServer('REQUEST_METHOD', '');
        $route      = $this->match($request);
        $groups     = ($route instanceof Route) ? $route->getGroups() : [];

        if (self::REQUEST_METHOD_HEAD == $method) {
            $response->disablePayload();
        }

        if (null !== $route) {
            return $this->execute($route, $request->getParams());
        } elseif (self::REQUEST_METHOD_OPTIONS == $method) {
            try {
                foreach ($groups as $group) {
                    if(isset(self::$options[$group])) {
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
                    $this->resources['error'] = $e;
                    \call_user_func_array($error['callback'], $this->getResources($error['resources']));
                }
            }
        } else {
            foreach (self::$errors['*'] as $error) { // Global error hooks
                $this->resources['error'] = new Exception('Not Found', 404);
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
     * @throws Exception
     */
    protected function validate(string $key, array $param, $value)
    {
        if ('' !== $value) {
            // checking whether the class exists
            $validator = $param['validator'];

            if (\is_callable($validator)) {
                $validator = \call_user_func_array($validator, $this->getResources($param['resources']));
            }

            // is the validator object an instance of the Validator class
            if (!$validator instanceof Validator) {
                throw new Exception('Validator object is not an instance of the Validator class', 500);
            }

            if (!$validator->isValid($value)) {
                throw new Exception('Invalid ' .$key . ': ' . $validator->getDescription(), 400);
            }
        } elseif (!$param['optional']) {
            throw new Exception('Param "' . $key . '" is not optional.', 400);
        }
    }
}
