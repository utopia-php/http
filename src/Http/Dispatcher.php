<?php

namespace Utopia\Http;

/**
 * Per-request request dispatcher.
 *
 * Owns the mutable state for a single request/response cycle so the
 * {@see Http} singleton carries only bootstrap configuration. This keeps
 * the library safe under concurrent request handling (Swoole coroutines).
 *
 * One Dispatcher instance is constructed per request by {@see Http::run()}.
 * @internal
 */
final class Dispatcher
{
    private ?RouteMatch $match = null;

    public function __construct(
        private readonly Http $http,
        private readonly Request $request,
        private readonly Response $response,
    ) {}

    public function matchedRoute(): ?Route
    {
        return $this->match?->route;
    }

    public function matchedRouteMatch(): ?RouteMatch
    {
        return $this->match;
    }

    public function handle(): void
    {
        if ($this->http->isCompressionEnabled()) {
            $this->response->setAcceptEncoding($this->request->getHeader('accept-encoding', ''));
            $this->response->setCompressionMinSize($this->http->getCompressionMinSize());
            $this->response->setCompressionSupported($this->http->getCompressionSupported());
        }

        $context = new RequestContext();
        $this->http->setRequestResource('request', fn() => $this->request);
        $this->http->setRequestResource('response', fn() => $this->response);
        $this->http->setRequestResource('context', fn() => $context);

        try {
            foreach (Http::getRequestHooks() as $hook) {
                $arguments = $this->http->getArguments($hook, [], []);
                \call_user_func_array($hook->getAction(), $arguments);
            }
        } catch (\Exception $e) {
            $this->http->setRequestResource('error', fn() => $e);

            foreach (Http::getErrorHooks() as $error) {
                if (\in_array('*', $error->getGroups())) {
                    try {
                        $arguments = $this->http->getArguments($error, [], []);
                        \call_user_func_array($error->getAction(), $arguments);
                    } catch (\Throwable $e) {
                        throw new Exception('Error handler had an error: ' . $e->getMessage(), 500, $e);
                    }
                }
            }
        }

        if ($this->http->isFileLoaded($this->request->getURI())) {
            $time = (60 * 60 * 24 * 365 * 2);

            $this->response
                ->setContentType($this->http->getFileMimeType($this->request->getURI()))
                ->addHeader('Cache-Control', 'public, max-age=' . $time)
                ->addHeader('Expires', \date('D, d M Y H:i:s', \time() + $time) . ' GMT')
                ->send($this->http->getFileContents($this->request->getURI()));

            return;
        }

        $method = $this->request->getMethod();
        $url = \parse_url($this->request->getURI(), PHP_URL_PATH);
        $url = \is_string($url) ? ($url === '' ? '/' : $url) : '/';
        $matchMethod = (Http::REQUEST_METHOD_HEAD === $method) ? Http::REQUEST_METHOD_GET : $method;

        $this->match = Router::matchRoute($matchMethod, $url);

        if ($this->match === null && Http::getWildcardRoute() !== null) {
            $wildcard = Http::getWildcardRoute();
            $this->match = new RouteMatch($wildcard, $url, Router::WILDCARD_TOKEN, Router::WILDCARD_TOKEN);
        }

        $context->setMatch($this->match);
        $this->http->setRequestResource('route', fn() => $this->match?->route);
        $this->http->setRequestResource('routeMatch', fn() => $this->match);

        $groups = $this->match?->route->getGroups() ?? [];

        if (Http::REQUEST_METHOD_HEAD === $method) {
            $method = Http::REQUEST_METHOD_GET;
            $this->response->disablePayload();
        }

        if (Http::REQUEST_METHOD_OPTIONS === $method) {
            try {
                foreach ($groups as $group) {
                    foreach (Http::getOptionsHooks() as $option) {
                        if (\in_array($group, $option->getGroups())) {
                            \call_user_func_array($option->getAction(), $this->http->getArguments($option, [], $this->request->getParams()));
                        }
                    }
                }

                foreach (Http::getOptionsHooks() as $option) {
                    if (\in_array('*', $option->getGroups())) {
                        \call_user_func_array($option->getAction(), $this->http->getArguments($option, [], $this->request->getParams()));
                    }
                }
            } catch (\Throwable $e) {
                foreach (Http::getErrorHooks() as $error) {
                    if (\in_array('*', $error->getGroups())) {
                        $this->http->setRequestResource('error', fn() => $e);
                        \call_user_func_array($error->getAction(), $this->http->getArguments($error, [], $this->request->getParams()));
                    }
                }
            }

            return;
        }

        if ($this->match !== null) {
            $this->execute($this->match);

            return;
        }

        foreach (Http::getErrorHooks() as $error) {
            if (\in_array('*', $error->getGroups())) {
                $this->http->setRequestResource('error', fn() => new Exception('Not Found', 404));
                \call_user_func_array($error->getAction(), $this->http->getArguments($error, [], $this->request->getParams()));
            }
        }
    }

    public function execute(RouteMatch $match): void
    {
        $route = $match->route;
        $groups = $route->getGroups();
        $pathValues = $route->getPathValues($this->request, $match->preparedPath);
        $requestParams = $this->request->getParams();

        try {
            if ($route->getHook()) {
                foreach (Http::getInitHooks() as $hook) {
                    if (\in_array('*', $hook->getGroups())) {
                        \call_user_func_array($hook->getAction(), $this->http->getArguments($hook, $pathValues, $requestParams));
                    }
                }
            }

            foreach ($groups as $group) {
                foreach (Http::getInitHooks() as $hook) {
                    if (\in_array($group, $hook->getGroups())) {
                        \call_user_func_array($hook->getAction(), $this->http->getArguments($hook, $pathValues, $requestParams));
                    }
                }
            }

            if (!$this->response->isSent()) {
                \call_user_func_array($route->getAction(), $this->http->getArguments($route, $pathValues, $requestParams));
            }

            foreach ($groups as $group) {
                foreach (Http::getShutdownHooks() as $hook) {
                    if (\in_array($group, $hook->getGroups())) {
                        \call_user_func_array($hook->getAction(), $this->http->getArguments($hook, $pathValues, $requestParams));
                    }
                }
            }

            if ($route->getHook()) {
                foreach (Http::getShutdownHooks() as $hook) {
                    if (\in_array('*', $hook->getGroups())) {
                        \call_user_func_array($hook->getAction(), $this->http->getArguments($hook, $pathValues, $requestParams));
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->http->setRequestResource('error', fn() => $e);

            foreach ($groups as $group) {
                foreach (Http::getErrorHooks() as $error) {
                    if (\in_array($group, $error->getGroups())) {
                        try {
                            \call_user_func_array($error->getAction(), $this->http->getArguments($error, $pathValues, $requestParams));
                        } catch (\Throwable $e) {
                            throw new Exception('Error handler had an error: ' . $e->getMessage(), 500, $e);
                        }
                    }
                }
            }

            foreach (Http::getErrorHooks() as $error) {
                if (\in_array('*', $error->getGroups())) {
                    try {
                        \call_user_func_array($error->getAction(), $this->http->getArguments($error, $pathValues, $requestParams));
                    } catch (\Throwable $e) {
                        throw new Exception('Error handler had an error: ' . $e->getMessage(), 500, $e);
                    }
                }
            }
        }
    }
}
