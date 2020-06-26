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
     * Current running mode
     *
     * @var string
     */
    protected static $mode = '';

    /**
     * Error
     *
     * An error callback
     *
     * @var callback
     */
    protected static $error = null;

    /**
     * Init
     *
     * A callback function that is initialized on application start
     *
     * @var callback[]
     */
    protected static $init = [];

    /**
     * Shutdown
     *
     * A callback function that is initialized on application end
     *
     * @var callback[]
     */
    protected static $shutdown = [];

    /**
     * Options
     *
     * A callback function for options method requests
     *
     * @var callback[]
     */
    protected static $options = [];

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
     * @param $callback
     * @return $this
     */
    public static function init(callable $callback)
    {
        self::$init[] = $callback;
    }

    /**
     * Shutdown
     *
     * Set a callback function that will be initialized on application end
     *
     * @param $callback
     * @return $this
     */
    public static function shutdown(callable $callback)
    {
        self::$shutdown[] = $callback;
    }

    /**
     * Options
     *
     * Set a callback function for all request with options method
     *
     * @param $callback
     * @return $this
     */
    public static function options(callable $callback)
    {
        self::$options[] = $callback;
    }

    /**
     * Error
     *
     * An error callback for failed or no matched requests
     *
     * @param $callback
     * @return $this
     */
    public static function error(callable $callback)
    {
        self::$error = $callback;
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

        /*
         * Re-order array
         *
         * For route to work with similar links where one is shorter than other
         *  but both might match given pattern
         */
        \uksort(self::$routes[$method], function ($a, $b) {
            return \strlen($b) - \strlen($a);
        });

        \uksort(self::$routes[$method], function ($a, $b) {
            return \count(\explode('/', $b)) - \count(\explode('/', $a));
        });

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
     * Run
     *
     * This is the place to initialize any pre routing logic.
     * This is where you might want to parse the application current URL by any desired logic
     *
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function run(Request $request, Response $response): self
    {
        $keys       = [];
        $params     = [];
        $method     = $request->getServer('REQUEST_METHOD', '');
        $route      = $this->match($request);

        if (self::REQUEST_METHOD_HEAD == $method) {
            $response->disablePayload();
        }

        if (null !== $route) {
            // Extract keys from URL
            $keyRegex = '@^' . \preg_replace('@:[^/]+@', ':([^/]+)', $route->getURL()) . '$@';
            \preg_match($keyRegex, $route->getURL(), $keys);

            // Remove the first key and value ( corresponding to full regex match )
            \array_shift($keys);

            // combine keys and values to one array
            $values = \array_combine($keys, $this->matches);

            try {
                foreach (self::$init as $init) {
                    \call_user_func_array($init, []);
                }

                foreach ($route->getParams() as $key => $param) {
                    // Get value from route or request object
                    $value = isset($values[$key]) ? $values[$key] : $request->getParam($key, $param['default']);
                    $value = ($value === '') ? $param['default'] : $value;

                    $this->validate($key, $param, $value);

                    $params[$key] = $value;
                }

                // Call the callback with the matched positions as params
                \call_user_func_array($route->getAction(), $params);

                foreach (self::$shutdown as $shutdown) {
                    \call_user_func_array($shutdown, []);
                }
            } catch (\Exception $e) {
                \call_user_func_array(self::$error, [$e]);
            }

            return $this;
        } elseif (self::REQUEST_METHOD_OPTIONS == $method) {
            try {
                foreach (self::$options as $option) {
                    \call_user_func_array($option, []);
                }
            } catch (\Exception $e) {
                \call_user_func_array(self::$error, [$e]);
            }
        } else {
            \call_user_func_array(self::$error, [new Exception('Not Found', 404)]);
        }

        return $this;
    }

    /**
     * Validate Param
     *
     * Creates an validator instance and validate given value with given rules.
     *
     * @param $key
     * @param $param
     * @param $value
     * @throws Exception
     */
    protected function validate($key, $param, $value)
    {
        if ('' !== $value) {
            // checking whether the class exists
            $validator = $param['validator'];

            if (\is_callable($validator)) {
                $validator = $validator();
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
