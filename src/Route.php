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

    protected bool $isActive = true;

    protected array $pathParams = [];

    public function __construct(string $method, string $path)
    {
        $this->path($path);
        $this->method = $method;
        $this->action = function (): void {
        };
    }

    /**
     * Add path
     */
    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    //TODO: remove
    /**
     * Set isActive
     */
    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    /**
     * Set hook status
     * When set false, hooks for this route will be skipped.
     */
    public function hook(bool $hook = true): static
    {
        $this->hook = $hook;

        return $this;
    }

    /**
     * When set to false the route will be skipped
     */
    public function getIsActive(): bool
    {
        return $this->isActive;
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

    public function setPathParam(string $key, int $index): void
    {
        $this->pathParams[$key] = $index;
    }

    public function getPathValues(Request $request): array
    {
        $pathParams = [];
        $parts = explode('/', ltrim($request->getURI(), '/'));

        foreach ($this->pathParams as $key => $index) {
            $pathParams[$key] = $parts[$index];
        }

        return $pathParams;
    }
}
