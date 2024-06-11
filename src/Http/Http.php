<?php

namespace Utopia\Http;

use Utopia\DI\Container;
use Utopia\DI\Dependency;
use Utopia\Servers\Base;

class Http extends Base
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
     * @var Files
     */
    protected Files $files;

    /**
     * Options
     *
     * A callback function for options method requests
     *
     * @var Hook[]
     */
    protected static array $options = [];

    /**
     * Wildcard route
     * If set, this get's executed if no other route is matched
     *
     * @var Route|null
     */
    protected static ?Route $wildcardRoute = null;

    /**
     * @var Adapter
     */
    protected Adapter $server;

    protected string|null $requestClass = null;
    protected string|null $responseClass = null;

    /**
     * Http
     *
     * @param Adapter $server
     * @param  string  $timezone
     */
    public function __construct(Adapter $server, Container $container, string $timezone)
    {
        \date_default_timezone_set($timezone);
        $this->files = new Files();
        $this->server = $server;
        $this->container = $container;
    }

    /**
     * Set Request Class
     */
    public function setResponseClass(string $responseClass)
    {
        $this->responseClass = $responseClass;
    }

    /**
     * Set Request Class
     */
    public function setRequestClass(string $requestClass)
    {
        $this->requestClass = $requestClass;
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
        $route = new Route('', '');

        self::$wildcardRoute = $route;

        return $route;
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
     * Load directory.
     *
     * @param  string  $directory
     * @param  string|null  $root
     * @return void
     *
     * @throws \Exception
    */
    public function loadFiles(string $directory, string $root = null): void
    {
        $this->files->load($directory, $root);
    }

    /**
     * Is file loaded.
     *
     * @param  string  $uri
     * @return bool
     */
    protected function isFileLoaded(string $uri): bool
    {
        return $this->files->isFileLoaded($uri);
    }

    /**
     * Get file contents.
     *
     * @param  string  $uri
     * @return string
     *
     * @throws \Exception
     */
    protected function getFileContents(string $uri): mixed
    {
        return $this->files->getFileContents($uri);
    }

    /**
     * Get file MIME type.
     *
     * @param  string  $uri
     * @return string
     *
     * @throws \Exception
     */
    protected function getFileMimeType(string $uri): mixed
    {
        return $this->files->getFileMimeType($uri);
    }

    public function start()
    {
        $this->server->onRequest(function ($request, $response) {
            $dependency = new Dependency();

            if(!\is_null($this->requestClass)) {
                $request = new $this->requestClass($request);
            }

            if(!\is_null($this->responseClass)) {
                $response = new $this->responseClass($response);
            }

            $context = clone $this->container;

            $context->set(
                clone $dependency
                    ->setName('request')
                    ->setCallback(fn () => $request)
            )
                ->set(
                    clone $dependency
                    ->setName('response')
                    ->setCallback(fn () => $response)
                )
                ->set(
                    clone $dependency
                        ->setName('http')
                        ->setCallback(fn () => $this)
                )
                ->set(
                    clone $dependency
                        ->setName('context')
                        ->setCallback(fn () => $context)
                )
            ;

            $this->run($context);
        });

        $this->server->onStart(function () {
            $container = clone $this->container;

            $dependency = new Dependency();
            $container
                ->set(
                    $dependency
                    ->setName('server')
                    ->setCallback(fn () => $this->server)
                )
            ;

            try {
                foreach (self::$start as $hook) {
                    $this->prepare($container, $hook, [], [])->inject($hook, true);
                }
            } catch(\Exception $e) {

                $dependency = new Dependency();
                $container->set(
                    $dependency
                        ->setName('error')
                        ->setCallback(fn () => $e)
                )
                ;

                foreach (self::$errors as $error) { // Global error hooks
                    if (in_array('*', $error->getGroups())) {
                        try {
                            $this->prepare($container, $error, [], [])->inject($error, true);
                        } catch (\Throwable $e) {
                            throw new Exception('Error handler had an error: ' . $e->getMessage(). ' on: ' . $e->getFile().':'.$e->getLine(), 500, $e);
                        }
                    }
                }
            }
        });

        $this->server->start();
    }

    /**
     * Match
     *
     * Find matching route given current user request
     *
     * @param  Request  $request
     * @return null|Route
     */
    public function match(Request $request): ?Route
    {
        $url = \parse_url($request->getURI(), PHP_URL_PATH);
        $method = $request->getMethod();
        $method = (self::REQUEST_METHOD_HEAD == $method) ? self::REQUEST_METHOD_GET : $method;

        return Router::match($method, $url);
    }

    /**
     * Execute a given route with middlewares and error handling
     *
     * @param  Route  $route
     * @param  Request  $request
     */
    protected function lifecycle(Route $route, Request $request, Container $context): static
    {
        $groups = $route->getGroups();
        $pathValues = $route->getPathValues($request);

        try {
            if ($route->getHook()) {
                foreach (self::$init as $hook) { // Global init hooks
                    if (in_array('*', $hook->getGroups())) {
                        $this->prepare($context, $hook, $pathValues, $request->getParams())->inject($hook, true);
                    }
                }
            }

            foreach ($groups as $group) {
                foreach (self::$init as $hook) { // Group init hooks
                    if (in_array($group, $hook->getGroups())) {
                        $this->prepare($context, $hook, $pathValues, $request->getParams())->inject($hook, true);
                    }
                }
            }

            $this->prepare($context, $route, $pathValues, $request->getParams())->inject($route, true);

            foreach ($groups as $group) {
                foreach (self::$shutdown as $hook) { // Group shutdown hooks
                    if (in_array($group, $hook->getGroups())) {
                        $this->prepare($context, $hook, $pathValues, $request->getParams())->inject($hook, true);
                    }
                }
            }

            if ($route->getHook()) {
                foreach (self::$shutdown as $hook) { // Global shutdown hooks
                    if (in_array('*', $hook->getGroups())) {
                        $this->prepare($context, $hook, $pathValues, $request->getParams())->inject($hook, true);
                    }
                }
            }
        } catch (\Throwable $e) {
            $dependency = new Dependency();
            $context->set(
                $dependency
                    ->setName('error')
                    ->setCallback(fn () => $e)
            )
            ;

            foreach ($groups as $group) {
                foreach (self::$errors as $error) { // Group error hooks
                    if (in_array($group, $error->getGroups())) {
                        try {
                            $this->prepare($context, $error, $pathValues, $request->getParams())->inject($error, true);
                        } catch (\Throwable $e) {
                            throw new Exception('Group error handler had an error: ' . $e->getMessage(). ' on: ' . $e->getFile().':'.$e->getLine(), 500, $e);
                        }
                    }
                }
            }

            foreach (self::$errors as $error) { // Global error hooks
                if (in_array('*', $error->getGroups())) {
                    try {
                        $this->prepare($context, $error, $pathValues, $request->getParams())->inject($error, true);
                    } catch (\Throwable $e) {
                        throw new Exception('Global error handler had an error: ' . $e->getMessage(). ' on: ' . $e->getFile().':'.$e->getLine(), 500, $e);
                    }
                }
            }
        }

        unset($context);

        return $this;
    }

    /**
     * Run
     *
     * This is the place to initialize any pre routing logic.
     * This is where you might want to parse the application current URL by any desired logic
     *
     * @param Container $context
     */
    public function run(Container $context): static
    {
        $request = $context->get('request'); /** @var Request $request */
        $response = $context->get('response'); /** @var Response $response */

        if ($this->isFileLoaded($request->getURI())) {
            $time = (60 * 60 * 24 * 365 * 2); // 45 days cache

            $response
                ->setContentType($this->getFileMimeType($request->getURI()))
                ->addHeader('Cache-Control', 'public, max-age=' . $time)
                ->addHeader('Expires', \date('D, d M Y H:i:s', \time() + $time) . ' GMT') // 45 days cache
                ->send($this->getFileContents($request->getURI()));

            return $this;
        }

        $method = $request->getMethod();
        $route = $this->match($request);
        $groups = ($route instanceof Route) ? $route->getGroups() : [];

        if (null === $route && null !== self::$wildcardRoute) {
            $route = self::$wildcardRoute;
            $path = \parse_url($request->getURI(), PHP_URL_PATH);
            $route->path($path);
        }

        $dependency = new Dependency();
        $context->set(
            $dependency
                ->setName('route')
                ->setCallback(fn () => $route ?? new Route($request->getMethod(), $request->getURI()))
        );

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
                            $this->prepare($context, $option, [], $request->getParams())->inject($option, true);
                        }
                    }
                }

                foreach (self::$options as $option) { // Global options hooks
                    /** @var Hook $option */
                    if (in_array('*', $option->getGroups())) {
                        $this->prepare($context, $option, [], $request->getParams())->inject($option, true);
                    }
                }
            } catch (\Throwable $e) {
                foreach (self::$errors as $error) { // Global error hooks
                    /** @var Hook $error */
                    if (in_array('*', $error->getGroups())) {
                        $dependency = new Dependency();
                        $context->set(
                            $dependency
                                ->setName('error')
                                ->setCallback(fn () => $e)
                        )
                        ;

                        $this->prepare($context, $error, [], $request->getParams())->inject($error, true);
                    }
                }
            }

            return $this;
        }

        if (null !== $route) {
            return $this->lifecycle($route, $request, $context);
        } elseif (self::REQUEST_METHOD_OPTIONS == $method) {
            try {
                foreach ($groups as $group) {
                    foreach (self::$options as $option) { // Group options hooks
                        if (in_array($group, $option->getGroups())) {
                            $this->prepare($context, $option, [], $request->getParams())->inject($option, true);
                        }
                    }
                }

                foreach (self::$options as $option) { // Global options hooks
                    if (in_array('*', $option->getGroups())) {
                        $this->prepare($context, $option, [], $request->getParams())->inject($option, true);
                    }
                }
            } catch (\Throwable $e) {
                foreach (self::$errors as $error) { // Global error hooks
                    if (in_array('*', $error->getGroups())) {
                        $dependency = new Dependency();
                        $context->set(
                            $dependency
                                ->setName('error')
                                ->setCallback(fn () => $e)
                        )
                        ;

                        $this->prepare($context, $error, [], $request->getParams())->inject($error, true);
                    }
                }
            }
        } else {
            foreach (self::$errors as $error) { // Global error hooks
                if (in_array('*', $error->getGroups())) {
                    $dependency = new Dependency();
                    $dependency
                        ->setName('error')
                        ->setCallback(fn () => new Exception('Not Found', 404));

                    $context->set($dependency);

                    $this->prepare($context, $error, [], $request->getParams())->inject($error, true);
                }
            }
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
        self::$options = [];
        parent::reset();
    }
}
