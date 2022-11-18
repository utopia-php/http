<?php

namespace Utopia;

use Exception;

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
     * Alias path
     *
     * @var null|string
     */
    protected ?string $aliasPath = null;

    /**
     * Array of aliases where key is the path and value is an array of params
     *
     * @var array
     */
    protected array $aliases = [];

    /**
     * Is Alias Route?
     * @var bool
     */
    protected bool $isAlias = false;

    /**
     * @var int
     */
    public static int $counter = 0;

    /**
     * @var int
     */
    protected int $order;

    /**
     * @var bool
     */
    protected bool $isActive = true;

    /**
     * @param string $method
     * @param string $path
     */
    public function __construct(string $method, string $path)
    {
        self::$counter++;

        $this->path($path);
        $this->method = $method;
        $this->order = self::$counter;
        $this->action = function (): void {
        };
    }

    /**
     * Add path
     *
     * @param string $path
     * @return static
     */
    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Add alias
     *
     * @param string $path
     * @param array $params
     * @return static
     */
    public function alias(string $path, array $params = []): static
    {
        $this->aliases[$path] = $params;

        return $this;
    }

    /**
     * Set isActive
     *
     * @param bool $isActive
     * @return void
     */
    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    /**
     * Set isAlias
     *
     * @param bool $isAlias
     *
     * @return void
     */
    public function setIsAlias(bool $isAlias): void
    {
        $this->isAlias = $isAlias;
    }

    /**
     * Set hook status
     * When set false, hooks for this route will be skipped.
     *
     * @param boolean $hook
     *
     * @return static
     */
    public function hook(bool $hook = true): static
    {
        $this->hook = $hook;

        return $this;
    }

    /**
     * When set to false the route will be skipped
     *
     * @return bool
     */
    public function getIsActive(): bool
    {
        return $this->isActive;
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
     * Returns an array where the keys are paths and values are params
     * 
     * @return array
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Get Alias path
     *
     * For backwards compatibility, returns the first alias path
     * 
     * @return string
     */
    public function getAliasPath(): string
    {
        if ($this->aliasPath !== null) {
            return $this->aliasPath;
        }

        $paths = array_keys($this->aliases);
        if (count($paths) === 0) {
            return '';
        }
        return $paths[0];
    }

    /**
     * Set Alias path
     *
     * For backwards compatibility, returns the first alias path
     * 
     * @return void
     */
    public function setAliasPath(?string $path): void
    {
        $this->aliasPath = $path;
        $this->setIsAlias($path !== null);
    }

    /**
     * Get Alias Params
     *
     * For backwards compatibility, returns the first alias params if no path passed
     * 
     * @return array
     */
    public function getAliasParams(string $path = null): array
    {
        if ($path === null) {
            $params = array_values($this->aliases);
            if (count($params) === 0) {
                return [];
            }
            return $params[0];
        }

        return $this->aliases[$path];
    }

    /**
     * Get is Alias
     *
     * @return bool
     */
    public function getIsAlias(): bool
    {
        return $this->isAlias;
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
     * Get hook status
     *
     * @return bool
     */
    public function getHook(): bool
    {
        return $this->hook;
    }
}