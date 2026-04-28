<?php

namespace Utopia\Http;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Utopia\DI\Container;
use Utopia\Servers\Hook;
use Utopia\Telemetry\Adapter as Telemetry;
use Utopia\Telemetry\Adapter\None as NoTelemetry;
use Utopia\Telemetry\Histogram;
use Utopia\Telemetry\UpDownCounter;
use Utopia\Validator;

class Http
{
    public const int COMPRESSION_MIN_SIZE_DEFAULT = 1024;
    public const int COMPRESSION_BROTLI_LEVEL_DEFAULT = 4;
    public const int COMPRESSION_ZSTD_LEVEL_DEFAULT = 3;

    /**
     * Request method constants
     */
    public const string REQUEST_METHOD_GET = 'GET';

    public const string REQUEST_METHOD_POST = 'POST';

    public const string REQUEST_METHOD_PUT = 'PUT';

    public const string REQUEST_METHOD_PATCH = 'PATCH';

    public const string REQUEST_METHOD_DELETE = 'DELETE';

    public const string REQUEST_METHOD_OPTIONS = 'OPTIONS';

    public const string REQUEST_METHOD_HEAD = 'HEAD';

    /**
     * Mode Type
     */
    public const string MODE_TYPE_DEVELOPMENT = 'development';

    public const string MODE_TYPE_STAGE = 'stage';

    public const string MODE_TYPE_PRODUCTION = 'production';

    protected Files $files;

    protected Container $container;

    /**
     * Current running mode
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
     * Server Start hooks
     *
     * @var Hook[]
     */
    protected static array $startHooks = [];

    /**
     * Request hooks
     *
     * @var Hook[]
     */
    protected static array $requestHooks = [];

    /**
     * Wildcard route
     * If set, this get's executed if no other route is matched
     */
    protected static ?Route $wildcardRoute = null;

    /**
     * Compression
     */
    protected bool $compression = false;

    protected int $compressionMinSize = Http::COMPRESSION_MIN_SIZE_DEFAULT;

    protected mixed $compressionSupported = [];

    private Histogram $requestDuration;

    private UpDownCounter $activeRequests;

    private Histogram $requestBodySize;

    private Histogram $responseBodySize;

    protected Adapter $server;

    /**
     * Http
     */
    public function __construct(Adapter $server, string $timezone)
    {
        date_default_timezone_set($timezone);
        $this->files = new Files();
        $this->server = $server;
        $this->container = $server->getContainer();
        $this->setTelemetry(new NoTelemetry());
    }

    /**
     * Set telemetry adapter.
     */
    public function setTelemetry(Telemetry $telemetry): void
    {
        // https://opentelemetry.io/docs/specs/semconv/http/http-metrics/#metric-httpserverrequestduration
        $this->requestDuration = $telemetry->createHistogram(
            'http.server.request.duration',
            's',
            null,
            ['ExplicitBucketBoundaries' => [0.005, 0.01, 0.025, 0.05, 0.075, 0.1, 0.25, 0.5, 0.75, 1, 2.5, 5, 7.5, 10]],
        );

        // https://opentelemetry.io/docs/specs/semconv/http/http-metrics/#metric-httpserveractive_requests
        $this->activeRequests = $telemetry->createUpDownCounter('http.server.active_requests', '{request}');
        // https://opentelemetry.io/docs/specs/semconv/http/http-metrics/#metric-httpserverrequestbodysize
        $this->requestBodySize = $telemetry->createHistogram('http.server.request.body.size', 'By');
        // https://opentelemetry.io/docs/specs/semconv/http/http-metrics/#metric-httpserverresponsebodysize
        $this->responseBodySize = $telemetry->createHistogram('http.server.response.body.size', 'By');
    }

    /**
     * Set Compression
     */
    public function setCompression(bool $compression): void
    {
        $this->compression = $compression;
    }

    /**
     * Set minimum compression size
     */
    public function setCompressionMinSize(int $compressionMinSize): void
    {
        $this->compressionMinSize = $compressionMinSize;
    }

    /**
     * Set supported compression algorithms
     */
    public function setCompressionSupported(mixed $compressionSupported): void
    {
        $this->compressionSupported = $compressionSupported;
    }

    /**
     * GET
     *
     * Add GET request route
     */
    public static function get(string $url): Route
    {
        return self::addRoute(self::REQUEST_METHOD_GET, $url);
    }

    /**
     * POST
     *
     * Add POST request route
     */
    public static function post(string $url): Route
    {
        return self::addRoute(self::REQUEST_METHOD_POST, $url);
    }

    /**
     * PUT
     *
     * Add PUT request route
     */
    public static function put(string $url): Route
    {
        return self::addRoute(self::REQUEST_METHOD_PUT, $url);
    }

    /**
     * PATCH
     *
     * Add PATCH request route
     */
    public static function patch(string $url): Route
    {
        return self::addRoute(self::REQUEST_METHOD_PATCH, $url);
    }

    /**
     * DELETE
     *
     * Add DELETE request route
     */
    public static function delete(string $url): Route
    {
        return self::addRoute(self::REQUEST_METHOD_DELETE, $url);
    }

    /**
     * Wildcard
     *
     * Add Wildcard route
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
     */
    public static function getEnv(string $key, ?string $default = null): ?string
    {
        return $_SERVER[$key] ?? $default;
    }

    /**
     * Get Mode
     *
     * Get current mode
     */
    public static function getMode(): string
    {
        return self::$mode;
    }

    /**
     * Set Mode
     *
     * Set current mode
     */
    public static function setMode(string $value): void
    {
        self::$mode = $value;
    }

    /**
     * Get allow override
     *
     */
    public static function getAllowOverride(): bool
    {
        return Router::getAllowOverride();
    }

    /**
     * Set Allow override
     *
     */
    public static function setAllowOverride(bool $value): void
    {
        Router::setAllowOverride($value);
    }

    /**
     * Get a single resource from the given scope.
     *
     * @throws Exception
     */
    public function getResource(string $name): mixed
    {
        try {
            return $this->server->getContext()->get($name);
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface $e) {
            // Normalize DI container errors to the Http layer's "resource" terminology.
            $message = str_replace('dependency', 'resource', $e->getMessage());

            if ($message === $e->getMessage() && !str_contains($message, 'resource')) {
                $message = 'Failed to find resource: "' . $name . '"';
            }

            throw new Exception($message, 500, $e);
        }
    }

    /**
     * Get multiple resources from the given scope.
     *
     * @param string[] $list
     * @return array<string, mixed>
     *
     * @throws Exception
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
     * Set a resource on the given scope.
     *
     * @param list<string> $injections
     */
    public function setResource(string $name, callable $callback, array $injections = []): void
    {
        $this->container->set($name, $callback, $injections);
    }

    /**
     * Set a request-scoped value on the current request's context container.
     *
     * The framework convention is: `setResource()` registers singletons on
     * the global container; `setContext()` registers per-request values on
     * the adapter's request container, which is coroutine-local under the
     * Swoole adapters. `getResource()` reads from the request container
     * with parent-fallback to the global one, so either kind resolves.
     *
     * @param list<string> $injections
     */
    protected function setContext(string $name, callable $callback, array $injections = []): void
    {
        $this->server->getContext()->set($name, $callback, $injections);
    }

    /**
     * Is http in production mode?
     */
    public static function isProduction(): bool
    {
        return self::MODE_TYPE_PRODUCTION === self::$mode;
    }

    /**
     * Is http in development mode?
     */
    public static function isDevelopment(): bool
    {
        return self::MODE_TYPE_DEVELOPMENT === self::$mode;
    }

    /**
     * Is http in stage mode?
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
     * @return array<string, Route[]>
     */
    public static function getRoutes(): array
    {
        return Router::getRoutes();
    }

    /**
     * Add Route
     *
     * Add routing route method, path and callback
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
     *
     * @throws \Exception
     */
    public function loadFiles(string $directory, ?string $root = null): void
    {
        $this->files->load($directory, $root);
    }

    /**
     * Is file loaded.
     */
    protected function isFileLoaded(string $uri): bool
    {
        return $this->files->isFileLoaded($uri);
    }

    /**
     * Get file contents.
     *
     * @return string
     * @throws \Exception
     */
    protected function getFileContents(string $uri): mixed
    {
        return $this->files->getFileContents($uri);
    }

    /**
     * Get file MIME type.
     *
     * @return string
     * @throws \Exception
     */
    protected function getFileMimeType(string $uri): mixed
    {
        return $this->files->getFileMimeType($uri);
    }

    public static function onStart(): Hook
    {
        $hook = new Hook();
        self::$startHooks[] = $hook;
        return $hook;
    }

    public static function onRequest(): Hook
    {
        $hook = new Hook();
        self::$requestHooks[] = $hook;
        return $hook;
    }

    public function start(): void
    {

        $this->server->onRequest(
            fn(Request $request, Response $response) => $this->run($request, $response),
        );

        $this->server->onStart(function ($server) {
            $this->setResource('server', fn() => $server);
            try {

                foreach (self::$startHooks as $hook) {
                    $arguments = $this->getArguments($hook, [], []);
                    \call_user_func_array($hook->getAction(), $arguments);
                }
            } catch (\Exception $e) {
                $this->setResource('error', fn() => $e);

                foreach (self::$errors as $error) { // Global error hooks
                    if (\in_array('*', $error->getGroups())) {
                        try {
                            $arguments = $this->getArguments($error, [], []);
                            \call_user_func_array($error->getAction(), $arguments);
                        } catch (\Throwable $e) {
                            throw new Exception('Error handler had an error: ' . $e->getMessage(), 500, $e);
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
     * @param  bool  $fresh If true, will not match any cached route
     */
    public function match(Request $request, bool $fresh = true): ?Route
    {
        $context = $this->server->getContext();

        if (!$fresh && $context->has('route')) {
            $cached = $context->get('route');
            if (null !== $cached) {
                return $cached;
            }
        }

        $url = parse_url($request->getURI(), PHP_URL_PATH);
        $url = \is_string($url) ? ($url === '' ? '/' : $url) : '/';
        $method = $request->getMethod();
        $method = (self::REQUEST_METHOD_HEAD === $method) ? self::REQUEST_METHOD_GET : $method;

        $matched = Router::match($method, $url);
        if (null === $matched) {
            $context->set('route', fn() => null);
            $context->set('matchedPath', fn() => '');
            return null;
        }

        [$route, $matchedPath] = $matched;
        $context->set('route', fn() => $route);
        $context->set('matchedPath', fn() => $matchedPath);

        return $route;
    }

    /**
     * Execute a given route with middlewares and error handling
     */
    public function execute(Route $route, Request $request, Response $response): static
    {
        $arguments = [];
        $groups = $route->getGroups();

        $context = $this->server->getContext();
        $matchedPath = $context->has('matchedPath') ? $context->get('matchedPath') : '';
        $preparedPath = Router::preparePath($matchedPath);
        $pathValues = $route->getPathValues($request, $preparedPath[0]);

        try {
            if ($route->getHook()) {
                foreach (self::$init as $hook) { // Global init hooks
                    if (\in_array('*', $hook->getGroups())) {
                        $arguments = $this->getArguments($hook, $pathValues, $request->getParams());
                        \call_user_func_array($hook->getAction(), $arguments);
                    }
                }
            }

            foreach ($groups as $group) {
                foreach (self::$init as $hook) { // Group init hooks
                    if (\in_array($group, $hook->getGroups())) {
                        $arguments = $this->getArguments($hook, $pathValues, $request->getParams());
                        \call_user_func_array($hook->getAction(), $arguments);
                    }
                }
            }

            if (!$response->isSent()) {
                $arguments = $this->getArguments($route, $pathValues, $request->getParams());

                // Stash a name-keyed map of the route's resolved+validated
                // params on the context so shutdown / error hooks can read
                // the same values the action saw — e.g. for label
                // substitution like {request.fileId}. Race-free because
                // the context container is per-request.
                $resolved = [];
                foreach ($route->getParams() as $name => $param) {
                    $resolved[$name] = $arguments[$param['order']] ?? null;
                }
                $this->setContext('arguments', fn() => $resolved);

                \call_user_func_array($route->getAction(), $arguments);
            }

            foreach ($groups as $group) {
                foreach (self::$shutdown as $hook) { // Group shutdown hooks
                    if (\in_array($group, $hook->getGroups())) {
                        $arguments = $this->getArguments($hook, $pathValues, $request->getParams());
                        \call_user_func_array($hook->getAction(), $arguments);
                    }
                }
            }

            if ($route->getHook()) {
                foreach (self::$shutdown as $hook) { // Group shutdown hooks
                    if (\in_array('*', $hook->getGroups())) {
                        $arguments = $this->getArguments($hook, $pathValues, $request->getParams());
                        \call_user_func_array($hook->getAction(), $arguments);
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->setContext('error', fn() => $e, []);

            foreach ($groups as $group) {
                foreach (self::$errors as $error) { // Group error hooks
                    if (\in_array($group, $error->getGroups())) {
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
                if (\in_array('*', $error->getGroups())) {
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
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $requestParams
     * @return array<int, mixed>
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
            if (\is_callable($arg) && !\is_string($arg)) {
                $arg = \call_user_func_array($arg, array_values($this->getResources($param['injections'])));
            }
            $value = $existsInValues ? $values[$key] : $arg;

            if (!$param['skipValidation']) {
                if (!$paramExists && !$param['optional']) {
                    throw new Exception('Param "' . $key . '" is not optional.', 400);
                }

                if ($paramExists) {
                    $this->validate($key, $param, $value);
                }
            }

            $arguments[$param['order']] = $value;
        }

        foreach ($hook->getInjections() as $injection) {
            $arguments[$injection['order']] = $this->getResource($injection['name']);
        }

        return $arguments;
    }

    /**
     * Run: wrapper function to record telemetry. All domain logic should happen in `runInternal`.
     */
    public function run(Request $request, Response $response): static
    {
        $this->activeRequests->add(1, [
            'http.request.method' => $request->getMethod(),
            'url.scheme' => $request->getProtocol(),
        ]);

        $start = microtime(true);
        $result = $this->runInternal($request, $response);

        $requestDuration = microtime(true) - $start;
        $context = $this->server->getContext();
        $route = $context->has('route') ? $context->get('route') : null;
        $attributes = [
            'url.scheme' => $request->getProtocol(),
            'http.request.method' => $request->getMethod(),
            'http.route' => $route?->getPath(),
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
     * Run internal
     *
     * This is the place to initialize any pre routing logic.
     * This is where you might want to parse the application current URL by any desired logic
     *
     * @param Response $response;
     */
    private function runInternal(Request $request, Response $response): static
    {
        if ($this->compression) {
            $response->setAcceptEncoding($request->getHeader('accept-encoding', ''));
            $response->setCompressionMinSize($this->compressionMinSize);
            $response->setCompressionSupported($this->compressionSupported);
        }

        $this->setContext('request', fn() => $request);
        $this->setContext('response', fn() => $response);

        try {
            foreach (self::$requestHooks as $hook) {
                $arguments = $this->getArguments($hook, [], []);
                \call_user_func_array($hook->getAction(), $arguments);
            }
        } catch (\Exception $e) {
            $this->setContext('error', fn() => $e, []);

            foreach (self::$errors as $error) { // Global error hooks
                if (\in_array('*', $error->getGroups())) {
                    try {
                        $arguments = $this->getArguments($error, [], []);
                        \call_user_func_array($error->getAction(), $arguments);
                    } catch (\Throwable $e) {
                        throw new Exception('Error handler had an error: ' . $e->getMessage(), 500, $e);
                    }
                }
            }
        }

        if ($this->isFileLoaded($request->getURI())) {
            $time = (60 * 60 * 24 * 365 * 2); // 45 days cache

            $response
                ->setContentType($this->getFileMimeType($request->getURI()))
                ->addHeader('Cache-Control', 'public, max-age=' . $time)
                ->addHeader('Expires', date('D, d M Y H:i:s', time() + $time) . ' GMT') // 45 days cache
                ->send($this->getFileContents($request->getURI()));

            return $this;
        }

        $method = $request->getMethod();
        $route = $this->match($request);
        $groups = ($route instanceof Route) ? $route->getGroups() : [];

        $this->setContext('route', fn() => $route, []);

        if (self::REQUEST_METHOD_HEAD === $method) {
            $method = self::REQUEST_METHOD_GET;
            $response->disablePayload();
        }

        if (self::REQUEST_METHOD_OPTIONS === $method) {
            try {
                foreach ($groups as $group) {
                    foreach (self::$options as $option) { // Group options hooks
                        /** @var Hook $option */
                        if (\in_array($group, $option->getGroups())) {
                            \call_user_func_array($option->getAction(), $this->getArguments($option, [], $request->getParams()));
                        }
                    }
                }

                foreach (self::$options as $option) { // Global options hooks
                    /** @var Hook $option */
                    if (\in_array('*', $option->getGroups())) {
                        \call_user_func_array($option->getAction(), $this->getArguments($option, [], $request->getParams()));
                    }
                }
            } catch (\Throwable $e) {
                foreach (self::$errors as $error) { // Global error hooks
                    /** @var Hook $error */
                    if (\in_array('*', $error->getGroups())) {
                        $this->setContext('error', fn() => $e, []);
                        \call_user_func_array($error->getAction(), $this->getArguments($error, [], $request->getParams()));
                    }
                }
            }

            return $this;
        }

        if (null === $route && null !== self::$wildcardRoute) {
            // Clone so we can stamp the request URI onto $path without
            // mutating the singleton shared across concurrent coroutines.
            $route = clone self::$wildcardRoute;
            $path = parse_url($request->getURI(), PHP_URL_PATH);
            $path = \is_string($path) ? ($path === '' ? '/' : $path) : '/';
            $route->path($path);

            $this->setContext('route', fn() => $route);
        }
        if (null !== $route) {
            return $this->execute($route, $request, $response);
        }

        if (self::REQUEST_METHOD_OPTIONS === $method) {
            try {
                foreach ($groups as $group) {
                    foreach (self::$options as $option) { // Group options hooks
                        if (\in_array($group, $option->getGroups())) {
                            \call_user_func_array($option->getAction(), $this->getArguments($option, [], $request->getParams()));
                        }
                    }
                }

                foreach (self::$options as $option) { // Global options hooks
                    if (\in_array('*', $option->getGroups())) {
                        \call_user_func_array($option->getAction(), $this->getArguments($option, [], $request->getParams()));
                    }
                }
            } catch (\Throwable $e) {
                foreach (self::$errors as $error) { // Global error hooks
                    if (\in_array('*', $error->getGroups())) {
                        $this->setContext('error', fn() => $e, []);
                        \call_user_func_array($error->getAction(), $this->getArguments($error, [], $request->getParams()));
                    }
                }
            }
        } else {
            foreach (self::$errors as $error) { // Global error hooks
                if (\in_array('*', $error->getGroups())) {
                    $this->setContext('error', fn() => new Exception('Not Found', 404), []);
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
     * @param  array<string, mixed>  $param
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
            $validator = \call_user_func_array($validator, array_values($this->getResources($param['injections'])));
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
     */
    public static function reset(): void
    {
        Router::reset();
        self::$mode = '';
        self::$errors = [];
        self::$init = [];
        self::$shutdown = [];
        self::$options = [];
        self::$startHooks = [];
        self::$requestHooks = [];
        self::$wildcardRoute = null;
    }
}
