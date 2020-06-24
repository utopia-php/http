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
    protected $routes = [
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
    protected $mode = '';

    /**
     * Error
     *
     * An error callback
     *
     * @var callback
     */
    protected $error = null;

    /**
     * Init
     *
     * A callback function that is initialized on application start
     *
     * @var callback[]
     */
    protected $init = [];

    /**
     * Shutdown
     *
     * A callback function that is initialized on application end
     *
     * @var callback[]
     */
    protected $shutdown = [];

    /**
     * Options
     *
     * A callback function for options method requests
     *
     * @var callback[]
     */
    protected $options = [];

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
    public function __construct($timezone, $mode = self::MODE_TYPE_PRODUCTION)
    {
        \date_default_timezone_set($timezone);

        // Turn errors on when not in production or stage
        if ($mode != self::MODE_TYPE_PRODUCTION && $mode != self::MODE_TYPE_STAGE) {
            \ini_set('display_errors', 1);
            \ini_set('display_startup_errors', 1);
            \error_reporting(E_ALL);
        }

        $this->mode = $mode;
    }

    /**
     * GET
     *
     * Add GET request route
     *
     * @param string $url
     * @return Route
     */
    public function get($url)
    {
        return $this->addRoute(self::REQUEST_METHOD_GET, $url);
    }

    /**
     * POST
     *
     * Add POST request route
     *
     * @param string $url
     * @return Route
     */
    public function post($url)
    {
        return $this->addRoute(self::REQUEST_METHOD_POST, $url);
    }

    /**
     * PUT
     *
     * Add PUT request route
     *
     * @param string $url
     * @return Route
     */
    public function put($url)
    {
        return $this->addRoute(self::REQUEST_METHOD_PUT, $url);
    }

    /**
     * PATCH
     *
     * Add PATCH request route
     *
     * @param string $url
     * @return Route
     */
    public function patch($url)
    {
        return $this->addRoute(self::REQUEST_METHOD_PATCH, $url);
    }

    /**
     * DELETE
     *
     * Add DELETE request route
     *
     * @param string $url
     * @return Route
     */
    public function delete($url)
    {
        return $this->addRoute(self::REQUEST_METHOD_DELETE, $url);
    }

    /**
     * Init
     *
     * Set a callback function that will be initialized on application start
     *
     * @param $callback
     * @return $this
     */
    public function init($callback)
    {
        $this->init[] = $callback;
        return $this;
    }

    /**
     * Shutdown
     *
     * Set a callback function that will be initialized on application end
     *
     * @param $callback
     * @return $this
     */
    public function shutdown($callback)
    {
        $this->shutdown[] = $callback;
        return $this;
    }

    /**
     * Options
     *
     * Set a callback function for all request with options method
     *
     * @param $callback
     * @return $this
     */
    public function options($callback)
    {
        $this->options[] = $callback;
        return $this;
    }

    /**
     * Error
     *
     * An error callback for failed or no matched requests
     *
     * @param $callback
     * @return $this
     */
    public function error($callback)
    {
        $this->error = $callback;
        return $this;
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
    public function getEnv($key, $default = null)
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
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set Mode
     *
     * Set current mode
     *
     * @return string
     */
    public function setMode($value)
    {
        $this->mode = $value;

        return $this;
    }

    /**
     * Is app in production mode?
     */
    public function isProduction(): bool
    {
        return (self::MODE_TYPE_PRODUCTION === $this->mode);
    }

    /**
     * Is app in development mode?
     */
    public function isDevelopment(): bool
    {
        return (self::MODE_TYPE_DEVELOPMENT === $this->mode);
    }

    /**
     * Is app in stage mode?
     */
    public function isStage(): bool
    {
        return (self::MODE_TYPE_STAGE === $this->mode);
    }

    /**
     * Get Routes
     *
     * Get all application routes
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
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
    protected function addRoute($method, $url)
    {
        $route = new Route($method, $url);

        $this->routes[$method][$url] = $route;

        return $route;
    }

    //TODO consider adding support to middlewares

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

        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }

        /*
         * Re-order array
         *
         * For route to work with similar links where one is shorter than other
         *  but both might match given pattern
         */
        \uksort($this->routes[$method], function ($a, $b) {
            return \strlen($b) - \strlen($a);
        });

        \uksort($this->routes[$method], function ($a, $b) {
            return \count(\explode('/', $b)) - \count(\explode('/', $a));
        });

        foreach ($this->routes[$method] as  $route) {
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

        // Extract keys from URL
        $keyRegex = '@^' . \preg_replace('@:[^/]+@', ':([^/]+)', $route->getURL()) . '$@';
        \preg_match($keyRegex, $route->getURL(), $keys);

        // Remove the first key and value ( corresponding to full regex match )
        \array_shift($keys);

        // combine keys and values to one array
        $values = \array_combine($keys, $this->matches);

        try {
            foreach ($this->init as $init) {
                \call_user_func_array($init, []);
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
            \call_user_func_array($route->getAction(), $params);

            foreach ($this->shutdown as $shutdown) {
                \call_user_func_array($shutdown, []);
            }
        } catch (\Exception $e) {
            \call_user_func_array($this->error, [$e]);
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
    public function run(Request $request, Response $response)
    {
        $method     = $request->getServer('REQUEST_METHOD', '');
        $route      = $this->match($request);

        if (self::REQUEST_METHOD_HEAD == $method) {
            $response->disablePayload();
        }

        if (null !== $route) {
            return $this->execute($route, $request->getParams());
        } elseif (self::REQUEST_METHOD_OPTIONS == $method) {
            try {
                foreach ($this->options as $option) {
                    \call_user_func_array($option, []);
                }
            } catch (\Exception $e) {
                \call_user_func_array($this->error, [$e]);
            }
        } else {
            \call_user_func_array($this->error, [new Exception('Not Found', 404)]);
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
