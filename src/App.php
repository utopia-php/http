<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Core
 *
 * @link https://github.com/eldadfux/Utopia-PHP-Framework
 * @author Eldad Fux <eldad@fuxie.co.il>
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
     * Env Type
     */
    const ENV_TYPE_DEVELOPMENT  = 'development';
    const ENV_TYPE_BUILD        = 'build';
    const ENV_TYPE_STAGE        = 'stage';
    const ENV_TYPE_PRODUCTION   = 'production';

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
     * Current running environment
     *
     * @var string
     */
    protected $env = '';

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
    protected $init = array();

    /**
     * Shutdown
     *
     * A callback function that is initialized on application end
     *
     * @var callback[]
     */
    protected $shutdown = array();

    /**
     * Options
     *
     * A callback function for options method requests
     *
     * @var callback[]
     */
    protected $options = array();

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
     * @var null
     */
    protected $matches = array();

    /**
     * App
     *
     * @param string $timezone
     * @param bool $env When current environment
     */
    public function __construct($timezone, $env)
    {
        date_default_timezone_set($timezone);

        // Turn errors on when not in production or stage
        if($env != self::ENV_TYPE_PRODUCTION && $env != self::ENV_TYPE_STAGE) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }

        $this->env = $env;
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
     * Get Env
     *
     * Get current defined environment
     *
     * @return string
     */
    public function getEnv()
    {
        return $this->env;
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
    public function match(Request $request) {

        if(null !== $this->route) {
            return $this->route;
        }

        $url    = parse_url($request->getServer('REQUEST_URI', ''), PHP_URL_PATH);
        $method = $request->getServer('REQUEST_METHOD', '');
        $method = (self::REQUEST_METHOD_HEAD == $method) ? self::REQUEST_METHOD_GET : $method;

        if(!isset($this->routes[$method])) {
            $this->routes[$method] = array();
        }

        /*
         * Re-order array
         *
         * For route to work with similar links where one is shorter than other
         *  but both might match given pattern
         */
        uksort($this->routes[$method], function($a, $b) {
            return strlen($b) - strlen($a);
        });

        uksort($this->routes[$method], function($a, $b) {
            return count(explode('/', $b)) - count(explode('/', $a));
        });

        foreach($this->routes[$method] as  $route) {
            /* @var $route Route */

            // convert urls like '/users/:uid/posts/:pid' to regular expression
            $regex = '@' . preg_replace('@:[^/]+@', '([^/]+)', $route->getURL()) . '@';

            // Check if the current request matches the expression
            if (!preg_match($regex, $url, $this->matches)) {
                continue;
            }

            array_shift($this->matches);
            $this->route = $route;
            break;
        }

        if(!empty($this->route) && ('/' === $this->route->getURL()) && ($url != $this->route->getURL())) {
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
    public function run(Request $request, Response $response)
    {
        $keys       = array();
        $params     = array();
        $method     = $request->getServer('REQUEST_METHOD', '');
        $route      = $this->match($request);

        if(self::REQUEST_METHOD_HEAD == $method) {
            $response->disablePayload();
        }

        if(null !== $route) {
            // Extract keys from URL
            $keyRegex = '@^' . preg_replace('@:[^/]+@', ':([^/]+)', $route->getURL()) . '$@';
            preg_match($keyRegex, $route->getURL(), $keys);

            // Remove the first key and value ( corresponding to full regex match )
            array_shift($keys);

            // combine keys and values to one array
            $values = array_combine($keys, $this->matches);

            try {
                foreach($this->init as $init) {
                    call_user_func_array($init, array());
                }

                foreach($route->getParams() as $key => $param) {
                    // Get value from route or request object
                    $value = isset($values[$key]) ? $values[$key] : $request->getParam($key, $param['default']);

                    $this->validate($key, $param, $value);

                    $params[$key] = $value;
                }

                // Call the callback with the matched positions as params
                call_user_func_array($route->getAction(), $params);

                foreach($this->shutdown as $shutdown) {
                    call_user_func_array($shutdown, array());
                }
            }
            catch (\Exception $e) {
                call_user_func_array($this->error, array($e));
            }

            return $this;
        }
        elseif(self::REQUEST_METHOD_OPTIONS == $method) {
            try {
                foreach($this->options as $option) {
                    call_user_func_array($option, array());
                }
            }
            catch (\Exception $e) {
                call_user_func_array($this->error, array($e));
            }
        }
        else {
            call_user_func_array($this->error, array(new Exception('Not Found', 404)));
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

            if(is_callable($validator)) {
                $validator = $validator();
            }

            // is the validator object an instance of the Validator class
            if (!$validator instanceof Validator) {
                throw new Exception('Validator object is not an instance of the Validator class', 500);
            }

            if (!$validator->isValid($value)) {
                throw new Exception('Invalid ' .$key . ': ' . $validator->getDescription(), 400);
            }
        }
        else if (!$param['optional']) {
            throw new Exception('Param "' . $key . '" is not optional.', 400);
        }
    }
}