<?php

namespace Utopia;

class Route extends Hook
{
    /**
     * HTTP Method
     *
     * @var string
     */
    protected string $method = '';

    /**
     * Whether to use hook
     *
     * @var bool
     */
    protected bool $hook = true;

    /**
     * Path
     *
     * @var string
     */
    protected string $path = '';

    /**
     * Path params.
     *
     * @var array<string,string>
     */
    protected array $pathParams = [];

    /**
     * Internal counter.
     *
     * @var int
     */
    protected static int $counter = 0;

    /**
     * Route order ID.
     *
     * @var int
     */
    protected int $order;

    public function __construct(string $method, string $path)
    {
        $this->path($path);
        $this->method = $method;
        $this->order = ++self::$counter;
        $this->action = function (): void {
        };
    }

    /**
     * Get Route Order ID
     *
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * Add path
     *
     * @param string $path
     * @return self
     */
    public function path(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Add alias
     *
     * @param  string $path
     * @return self
     */
    public function alias(string $path): self
    {
        Router::addRouteAlias($path, $this);

        return $this;
    }

    /**
     * When set false, hooks for this route will be skipped.
     *
     * @param bool $hook
     * @return self
     */
    public function hook(bool $hook = true): self
    {
        $this->hook = $hook;

        return $this;
    }

    /**
     * Get HTTP Method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get hook status
     *
     * @return bool
     */
    public function getHook(): bool
    {
        return $this->hook;
    }

    /**
     * Set path param.
     *
     * @param string $key
     * @param int $index
     * @return void
     */
    public function setPathParam(string $key, int $index): void
    {
        $this->pathParams[$key] = $index;
    }

    /**
     * Get path params.
     *
     * @param \Utopia\Request $request
     * @return array
     */
    public function getPathValues(Request $request): array
    {
        $pathValues = [];
        $parts = explode('/', ltrim($request->getURI(), '/'));

        foreach ($this->pathParams as $key => $index) {
            if (array_key_exists($index, $parts)) {
                $pathValues[$key] = $parts[$index];
            }
        }

        return $pathValues;
    }
}
