<?php

namespace Utopia\Http;

use Utopia\Servers\Hook;

class Route extends Hook
{
    /**
     * HTTP Method
     */
    protected string $method = '';

    /**
     * Whether to use hook
     */
    protected bool $hook = true;

    /**
     * Path
     */
    protected string $path = '';

    /**
     * Path params.
     *
     * @var array<string, array<string, int>>
     */
    protected array $pathParams = [];

    /**
     * Internal counter.
     */
    protected static int $counter = 0;

    /**
     * Route order ID.
     */
    protected int $order;

    public function __construct(string $method, string $path)
    {
        parent::__construct();
        $this->path($path);
        $this->method = $method;
        $this->order = ++self::$counter;
    }

    /**
     * Get Route Order ID
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * Add path
     */
    public function path(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Add alias
     */
    public function alias(string $path): self
    {
        Router::addRouteAlias($path, $this);

        return $this;
    }

    /**
     * When set false, hooks for this route will be skipped.
     */
    public function hook(bool $hook = true): self
    {
        $this->hook = $hook;

        return $this;
    }

    /**
     * Get HTTP Method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get hook status
     */
    public function getHook(): bool
    {
        return $this->hook;
    }

    /**
     * Set path param.
     */
    public function setPathParam(string $key, int $index, string $path = ''): void
    {
        $this->pathParams[$path][$key] = $index;
    }

    /**
     * Resolve path params for the given request URL against a registered
     * template. Pass `''` to fall back to the route's first registered
     * template (correct only when there are no aliases with differing
     * placeholder positions).
     *
     * @return array<string, string>
     */
    public function resolveParams(string $url, string $matchedTemplate = ''): array
    {
        $pathValues = [];
        $parts = explode('/', ltrim($url, '/'));

        if (empty($matchedTemplate)) {
            $pathParams = $this->pathParams[$matchedTemplate] ?? array_values($this->pathParams)[0] ?? [];
        } else {
            $pathParams = $this->pathParams[$matchedTemplate] ?? [];
        }

        foreach ($pathParams as $key => $index) {
            if (\array_key_exists($index, $parts)) {
                $pathValues[$key] = $parts[$index];
            }
        }

        return $pathValues;
    }
}
