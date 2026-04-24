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
    public const COMPRESSION_MIN_SIZE_DEFAULT = 1024;
    public const COMPRESSION_BROTLI_LEVEL_DEFAULT = 4;
    public const COMPRESSION_ZSTD_LEVEL_DEFAULT = 3;

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
        \date_default_timezone_set($timezone);
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

    public function isCompressionEnabled(): bool
    {
        return $this->compression;
    }

    /**
     * Set minimum compression size
     */
    public function setCompressionMinSize(int $compressionMinSize): void
    {
        $this->compressionMinSize = $compressionMinSize;
    }

    public function getCompressionMinSize(): int
    {
        return $this->compressionMinSize;
    }

    /**
     * Set supported compression algorithms
     */
    public function setCompressionSupported(mixed $compressionSupported): void
    {
        $this->compressionSupported = $compressionSupported;
    }

    public function getCompressionSupported(): mixed
    {
        return $this->compressionSupported;
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
     * Returns the registered wildcard route, if any.
     *
     * The returned Route is a shared definition and MUST NOT be mutated by
     * request-handling code. Per-request state belongs on {@see RouteMatch}.
     */
    public static function getWildcardRoute(): ?Route
    {
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

    /** @return Hook[] */
    public static function getInitHooks(): array
    {
        return self::$init;
    }

    /** @return Hook[] */
    public static function getShutdownHooks(): array
    {
        return self::$shutdown;
    }

    /** @return Hook[] */
    public static function getOptionsHooks(): array
    {
        return self::$options;
    }

    /** @return Hook[] */
    public static function getErrorHooks(): array
    {
        return self::$errors;
    }

    /** @return Hook[] */
    public static function getStartHooks(): array
    {
        return self::$startHooks;
    }

    /** @return Hook[] */
    public static function getRequestHooks(): array
    {
        return self::$requestHooks;
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
            return $this->server->getContainer()->get($name);
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface $e) {
            // Normalize DI container errors to the Http layer's "resource" terminology.
            $message = \str_replace('dependency', 'resource', $e->getMessage());

            if ($message === $e->getMessage() && !\str_contains($message, 'resource')) {
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
     * Set a request-scoped resource on the current request's container.
     *
     * Relies on {@see Adapter::getContainer()} returning a container scoped
     * to the current request/coroutine. Swoole adapters back this with
     * `Coroutine::getContext()`; the FPM adapter has a single request per
     * process so the shared container is safe there.
     *
     * @param list<string> $injections
     */
    public function setRequestResource(string $name, callable $callback, array $injections = []): void
    {
        $this->server->getContainer()->set($name, $callback, $injections);
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
     * @deprecated Read the `routeMatch` or `route` request resource instead
     *   (e.g. `$http->getResource('routeMatch')?->route`). The per-request
     *   route lives in the per-request DI container; returning it from the
     *   shared Http singleton is not safe under concurrent request handling.
     */
    public function getRoute(): ?Route
    {
        try {
            $match = $this->server->getContainer()->get('routeMatch');
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface) {
            return null;
        }

        return $match instanceof RouteMatch ? $match->route : null;
    }

    /**
     * @deprecated Construct a {@see RouteMatch} and register it via
     *   `setRequestResource('routeMatch', ...)` instead. Provided as a shim
     *   for tests and legacy callers only.
     */
    public function setRoute(Route $route): self
    {
        $match = new RouteMatch($route, '', '', '');
        $this->setRequestResource('route', fn() => $route);
        $this->setRequestResource('routeMatch', fn() => $match);

        return $this;
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
    public function isFileLoaded(string $uri): bool
    {
        return $this->files->isFileLoaded($uri);
    }

    /**
     * Get file contents.
     *
     * @throws \Exception
     */
    public function getFileContents(string $uri): mixed
    {
        return $this->files->getFileContents($uri);
    }

    /**
     * Get file MIME type.
     *
     * @throws \Exception
     */
    public function getFileMimeType(string $uri): mixed
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

                foreach (self::$errors as $error) {
                    if (in_array('*', $error->getGroups())) {
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
     * @deprecated Use {@see Router::matchRoute()} which returns a per-request
     *   {@see RouteMatch}. This shim discards the `$fresh` argument: the
     *   previous implementation cached the match on the Http singleton,
     *   which is not safe under concurrent request handling.
     */
    public function match(Request $request, bool $fresh = true): ?Route
    {
        $url = \parse_url($request->getURI(), PHP_URL_PATH);
        $url = \is_string($url) ? ($url === '' ? '/' : $url) : '/';
        $method = $request->getMethod();
        $method = (self::REQUEST_METHOD_HEAD === $method) ? self::REQUEST_METHOD_GET : $method;

        $match = Router::matchRoute($method, $url);
        if ($match === null) {
            return null;
        }

        $this->setRequestResource('route', fn() => $match->route);
        $this->setRequestResource('routeMatch', fn() => $match);

        return $match->route;
    }

    /**
     * Execute a given route with middlewares and error handling.
     *
     * @deprecated Internal dispatch moved to {@see Dispatcher}. This shim
     *   remains for tests and callers that invoke `execute()` directly with a
     *   Route built outside the router; it synthesises a {@see RouteMatch}
     *   from the route's registered path.
     */
    public function execute(Route $route, Request $request, Response $response): static
    {
        [$preparedPath] = Router::preparePath($route->getPath());
        $urlPath = \parse_url($request->getURI(), PHP_URL_PATH);
        $urlPath = \is_string($urlPath) ? ($urlPath === '' ? '/' : $urlPath) : '/';
        $match = new RouteMatch($route, $urlPath, $preparedPath, $preparedPath);

        $this->setRequestResource('request', fn() => $request);
        $this->setRequestResource('response', fn() => $response);
        $this->setRequestResource('route', fn() => $route);
        $this->setRequestResource('routeMatch', fn() => $match);

        (new Dispatcher($this, $request, $response))->execute($match);

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
    public function getArguments(Hook $hook, array $values, array $requestParams): array
    {
        $arguments = [];
        foreach ($hook->getParams() as $key => $param) {
            $existsInRequest = \array_key_exists($key, $requestParams);
            $existsInValues = \array_key_exists($key, $values);
            $paramExists = $existsInRequest || $existsInValues;

            $arg = $existsInRequest ? $requestParams[$key] : $param['default'];
            if (\is_callable($arg) && !\is_string($arg)) {
                $arg = \call_user_func_array($arg, \array_values($this->getResources($param['injections'])));
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

            $hook->setParamValue($key, $value);
            $arguments[$param['order']] = $value;
        }

        foreach ($hook->getInjections() as $injection) {
            $arguments[$injection['order']] = $this->getResource($injection['name']);
        }

        return $arguments;
    }

    /**
     * Run: wrapper function to record telemetry. Dispatch lives in {@see Dispatcher}.
     */
    public function run(Request $request, Response $response): static
    {
        $this->activeRequests->add(1, [
            'http.request.method' => $request->getMethod(),
            'url.scheme' => $request->getProtocol(),
        ]);

        $start = microtime(true);

        $dispatcher = new Dispatcher($this, $request, $response);
        $dispatcher->handle();

        $requestDuration = microtime(true) - $start;
        $attributes = [
            'url.scheme' => $request->getProtocol(),
            'http.request.method' => $request->getMethod(),
            'http.route' => $dispatcher->matchedRoute()?->getPath(),
            'http.response.status_code' => $response->getStatusCode(),
        ];
        $this->requestDuration->record($requestDuration, $attributes);
        $this->requestBodySize->record($request->getSize(), $attributes);
        $this->responseBodySize->record($response->getSize(), $attributes);
        $this->activeRequests->add(-1, [
            'http.request.method' => $request->getMethod(),
            'url.scheme' => $request->getProtocol(),
        ]);

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

        $validator = $param['validator'];

        if (\is_callable($validator)) {
            $validator = \call_user_func_array($validator, \array_values($this->getResources($param['injections'])));
        }

        if (!$validator instanceof Validator) {
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
