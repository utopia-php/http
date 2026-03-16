<?php

namespace Utopia\Http;

use Utopia\DI\Container;
use Utopia\Servers\Base;
use Utopia\Telemetry\Adapter as Telemetry;
use Utopia\Telemetry\Adapter\None as NoTelemetry;
use Utopia\Telemetry\Histogram;
use Utopia\Telemetry\UpDownCounter;

class Http extends Base
{
    public const COMPRESSION_MIN_SIZE_DEFAULT = 1024;
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
     * Compression
     */
    protected bool $compression = false;
    protected int $compressionMinSize = self::COMPRESSION_MIN_SIZE_DEFAULT;
    protected mixed $compressionSupported = [];

    private Histogram $requestDuration;
    private UpDownCounter $activeRequests;
    private Histogram $requestBodySize;
    private Histogram $responseBodySize;

    /**
     * @var Adapter
     */
    protected Adapter $server;

    protected string|null $requestClass = null;
    protected string|null $responseClass = null;

    /**
     * Matched Route
     *
     * During runtime $this->route might be overwritten with the wildcard route to keep custom functions working with
     * paths not declared in the Router. Keep a copy of the original matched app route.
     */
    protected ?Route $matchedRoute = null;

    /**
     * Http
     *
     * @param Adapter $server
     * @param string $timezone
     */
    public function __construct(Adapter $server, Container $container, string $timezone)
    {
        \date_default_timezone_set($timezone);
        $this->files = new Files();
        $this->server = $server;
        $this->container = $container;
        $this->setTelemetry(new NoTelemetry());
    }

    /**
     * Set Compression
     */
    public function setCompression(bool $compression): static
    {
        $this->compression = $compression;
        return $this;
    }

    /**
     * Set minimum compression size
     */
    public function setCompressionMinSize(int $compressionMinSize): static
    {
        $this->compressionMinSize = $compressionMinSize;
        return $this;
    }

    /**
     * Set supported compression algorithms
     */
    public function setCompressionSupported(mixed $compressionSupported): static
    {
        $this->compressionSupported = $compressionSupported;
        return $this;
    }

    /**
     * Set telemetry adapter.
     *
     * @param Telemetry $telemetry
     * @return void
     */
    public function setTelemetry(Telemetry $telemetry): void
    {
        // https://opentelemetry.io/docs/specs/semconv/http/http-metrics/#metric-httpserverrequestduration
        $this->requestDuration = $telemetry->createHistogram(
            'http.server.request.duration',
            's',
            null,
            ['ExplicitBucketBoundaries' =>  [0.005, 0.01, 0.025, 0.05, 0.075, 0.1, 0.25, 0.5, 0.75, 1, 2.5, 5, 7.5, 10]]
        );

        // https://opentelemetry.io/docs/specs/semconv/http/http-metrics/#metric-httpserveractive_requests
        $this->activeRequests = $telemetry->createUpDownCounter('http.server.active_requests', '{request}');
        // https://opentelemetry.io/docs/specs/semconv/http/http-metrics/#metric-httpserverrequestbodysize
        $this->requestBodySize = $telemetry->createHistogram('http.server.request.body.size', 'By');
        // https://opentelemetry.io/docs/specs/semconv/http/http-metrics/#metric-httpserverresponsebodysize
        $this->responseBodySize = $telemetry->createHistogram('http.server.response.body.size', 'By');
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
     * Execute a hook by resolving its dependencies and calling its action.
     *
     * @param Container $scope
     * @param \Utopia\Servers\Hook $hook
     * @return void
     */
    protected function executeHook(Container $scope, \Utopia\Servers\Hook $hook): void
    {
        // Build ordered list of all dependencies (params + injections)
        $dependencies = [];

        foreach ($hook->getParams() as $key => $param) {
            $dependencies[] = ['name' => $key, 'order' => $param['order']];
        }

        foreach ($hook->getInjections() as $name => $injection) {
            $dependencies[] = $injection;
        }

        \usort($dependencies, fn ($a, $b) => $a['order'] <=> $b['order']);

        $args = \array_map(fn ($dep) => $scope->get($dep['name']), $dependencies);
        \call_user_func_array($hook->getAction(), $args);
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
     * @param bool $value
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
     * @param string $method
     * @param string $url
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
     * @param string $directory
     * @param string|null $root
     * @return void
     *
     * @throws \Exception
     */
    public function loadFiles(string $directory, ?string $root = null): void
    {
        $this->files->load($directory, $root);
    }

    /**
     * Is file loaded.
     *
     * @param string $uri
     * @return bool
     */
    protected function isFileLoaded(string $uri): bool
    {
        return $this->files->isFileLoaded($uri);
    }

    /**
     * Get file contents.
     *
     * @param string $uri
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
     * @param string $uri
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
            if (!\is_null($this->requestClass)) {
                $request = new $this->requestClass($request);
            }

            if (!\is_null($this->responseClass)) {
                $response = new $this->responseClass($response);
            }

            $container = new Container($this->container);

            $container->set('request', fn () => $request)
                ->set('response', fn () => $response);

            // More base injection for GraphQL only
            if ($request->getUri() === '/v1/graphql') {
                $container->set('http', fn () => $this)
                    ->set('context', fn () => $container);
            }


            $this->run($container);
        });

        $this->server->onStart(function () {
            $container = new Container($this->container);

            $container->set('server', fn () => $this->server);

            try {
                foreach (self::$start as $hook) {
                    $this->executeHook($this->prepare($container, $hook, [], []), $hook);
                }
            } catch (\Exception $e) {
                $container->set('error', fn () => $e);

                foreach (self::$errors as $error) { // Global error hooks
                    if (in_array('*', $error->getGroups())) {
                        try {
                            $this->executeHook($this->prepare($container, $error, [], []), $error);
                        } catch (\Throwable $e) {
                            throw new Exception('Error handler had an error: ' . $e->getMessage() . ' on: ' . $e->getFile() . ':' . $e->getLine(), 500, $e);
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
     * @param Request $request
     * @return null|Route
     */
    public function match(Request $request): ?Route
    {
        $url = \parse_url($request->getURI(), PHP_URL_PATH);

        if ($url === null || $url === false) {
            $url = '/'; // Default to root path for malformed URLs
        }

        $method = $request->getMethod();
        $method = (self::REQUEST_METHOD_HEAD == $method) ? self::REQUEST_METHOD_GET : $method;

        return Router::match($method, $url);
    }


    public function execute(Route $route, Request $request, Container $container): self
    {
        return $this->lifecycle($route, $request, $container);
    }

    /**
     * Execute a given route with middlewares and error handling
     *
     * @param Route $route
     * @param Request $request
     */
    protected function lifecycle(Route $route, Request $request, Container $container): static
    {
        $groups = $route->getGroups();
        $pathValues = $route->getPathValues($request);

        try {
            if ($route->getHook()) {
                foreach (self::$init as $hook) { // Global init hooks
                    if (in_array('*', $hook->getGroups())) {
                        $this->executeHook($this->prepare($container, $hook, $pathValues, $request->getParams()), $hook);
                    }
                }
            }

            foreach ($groups as $group) {
                foreach (self::$init as $hook) { // Group init hooks
                    if (in_array($group, $hook->getGroups())) {
                        $this->executeHook($this->prepare($container, $hook, $pathValues, $request->getParams()), $hook);
                    }
                }
            }

            $this->executeHook($this->prepare($container, $route, $pathValues, $request->getParams()), $route);

            foreach ($groups as $group) {
                foreach (self::$shutdown as $hook) { // Group shutdown hooks
                    if (in_array($group, $hook->getGroups())) {
                        $this->executeHook($this->prepare($container, $hook, $pathValues, $request->getParams()), $hook);
                    }
                }
            }

            if ($route->getHook()) {
                foreach (self::$shutdown as $hook) { // Global shutdown hooks
                    if (in_array('*', $hook->getGroups())) {
                        $this->executeHook($this->prepare($container, $hook, $pathValues, $request->getParams()), $hook);
                    }
                }
            }
        } catch (\Throwable $e) {
            $container->set('error', fn () => $e);

            foreach ($groups as $group) {
                foreach (self::$errors as $error) { // Group error hooks
                    if (in_array($group, $error->getGroups())) {
                        try {
                            $this->executeHook($this->prepare($container, $error, $pathValues, $request->getParams()), $error);
                        } catch (\Throwable $e) {
                            throw new Exception('Group error handler had an error: ' . $e->getMessage() . ' on: ' . $e->getFile() . ':' . $e->getLine(), 500, $e);
                        }
                    }
                }
            }

            foreach (self::$errors as $error) { // Global error hooks
                if (in_array('*', $error->getGroups())) {
                    try {
                        $this->executeHook($this->prepare($container, $error, $pathValues, $request->getParams()), $error);
                    } catch (\Throwable $e) {
                        throw new Exception('Global error handler had an error: ' . $e->getMessage() . ' on: ' . $e->getFile() . ':' . $e->getLine(), 500, $e);
                    }
                }
            }
        }

        unset($container);

        return $this;
    }

    public function run(Container $container): static
    {
        $request = $container->get('request');
        /** @var Request $request */
        $response = $container->get('response');
        /** @var Response $response */
        $route = $this->match($request);
        /** @var ?Route $route */
        $this->matchedRoute = $route;

        $this->activeRequests->add(1, [
            'http.request.method' => $request->getMethod(),
            'url.scheme' => $request->getProtocol(),
        ]);
        $start = microtime(true);
        $result = $this->runInternal($container, $route);

        $requestDuration = microtime(true) - $start;
        $attributes = [
            'url.scheme' => $request->getProtocol(),
            'http.request.method' => $request->getMethod(),
            'http.route' => $route?->getPath() ?? '',
            'http.response.status_code' => $response->getStatusCode(),
        ];
        $this->requestDuration->record($requestDuration, $attributes);
        $this->requestBodySize->record($request->getSize(), $attributes);
        $this->responseBodySize->record($response->getSize(), $attributes);
        $this->activeRequests->add(-1, [
            'http.request.method' => $request->getMethod(),
            'url.scheme' => $request->getProtocol(),
        ]);
        return $result;
    }


    /**
     * Run
     *
     * This is the place to initialize any pre routing logic.
     * This is where you might want to parse the application current URL by any desired logic
     *
     * @param Container $container
     */
    protected function runInternal(Container $container, ?Route $route): static
    {
        $request = $container->get('request');
        /** @var Request $request */
        $response = $container->get('response');
        /** @var Response $response */

        if ($this->compression) {
            $response->setAcceptEncoding($request->getHeader('accept-encoding', ''));
            $response->setCompressionMinSize($this->compressionMinSize);
            $response->setCompressionSupported($this->compressionSupported);
        }

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
        $groups = ($route instanceof Route) ? $route->getGroups() : [];

        if (null === $route && null !== self::$wildcardRoute) {
            $route = self::$wildcardRoute;
            $path = \parse_url($request->getURI(), PHP_URL_PATH);
            $route->path($path);
        }

        $container->set('route', fn () => $route ?? new Route($request->getMethod(), $request->getURI()));

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
                            $this->executeHook($this->prepare($container, $option, [], $request->getParams()), $option);
                        }
                    }
                }

                foreach (self::$options as $option) { // Global options hooks
                    /** @var Hook $option */
                    if (in_array('*', $option->getGroups())) {
                        $this->executeHook($this->prepare($container, $option, [], $request->getParams()), $option);
                    }
                }
            } catch (\Throwable $e) {
                foreach (self::$errors as $error) { // Global error hooks
                    /** @var Hook $error */
                    if (in_array('*', $error->getGroups())) {
                        $container->set('error', fn () => $e);

                        $this->executeHook($this->prepare($container, $error, [], $request->getParams()), $error);
                    }
                }
            }

            return $this;
        }

        if (null !== $route) {
            return $this->lifecycle($route, $request, $container);
        } elseif (self::REQUEST_METHOD_OPTIONS == $method) {
            try {
                foreach ($groups as $group) {
                    foreach (self::$options as $option) { // Group options hooks
                        if (in_array($group, $option->getGroups())) {
                            $this->executeHook($this->prepare($container, $option, [], $request->getParams()), $option);
                        }
                    }
                }

                foreach (self::$options as $option) { // Global options hooks
                    if (in_array('*', $option->getGroups())) {
                        $this->executeHook($this->prepare($container, $option, [], $request->getParams()), $option);
                    }
                }
            } catch (\Throwable $e) {
                foreach (self::$errors as $error) { // Global error hooks
                    if (in_array('*', $error->getGroups())) {
                        $container->set('error', fn () => $e);

                        $this->executeHook($this->prepare($container, $error, [], $request->getParams()), $error);
                    }
                }
            }
        } else {
            foreach (self::$errors as $error) { // Global error hooks
                if (in_array('*', $error->getGroups())) {
                    $container->set('error', fn () => new Exception('Not Found', 404));

                    $this->executeHook($this->prepare($container, $error, [], $request->getParams()), $error);
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
