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
     * Array of aliases.
     *
     * @var array<string>
     */
    protected array $aliases = [];

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

    public function __construct(string $method, string $path)
    {
        $this->path($path);
        $this->method = $method;
        $this->action = function (): void {
        };
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
     * @param  array $params
     * @return self
     */
    public function alias(string $path): self
    {
        if (!in_array($path, $this->aliases)) {
            $this->aliases[] = $path;
        }

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
     * Get Aliases
     *
     * @return array<string>
     */
    public function getAliases(): array
    {
        return $this->aliases;
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
        $pathParams = [];
        $parts = explode('/', ltrim($request->getURI(), '/'));

        foreach ($this->pathParams as $key => $index) {
            $pathParams[$key] = $parts[$index];
        }

        return $pathParams;
    }
}
