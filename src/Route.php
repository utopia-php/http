<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Core
 *
 * @link https://github.com/utopia-php/framework
 * @author Appwrite Team <team@appwrite.io>
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

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
     * @var string
     */
    protected string $aliasPath = '';

    /**
     * Alias Params
     *
     * @var array
     */
    protected array $aliasParams = [];

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
     * Labels
     *
     * List of route label names
     *
     * @var array
     */
    protected array $labels = [];

    /**
     * @var int
     */
    protected int $order;

    /**
     * @var bool
     */
    protected bool $active = true;

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
        $this->action = function (): void {};
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
        $this->aliasPath = $path;
        $this->aliasParams = $params;

        return $this;
    }

    /**
     * Set active
     *
     * @param bool $active
     * @return void
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
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
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * Add Label
     *
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function label(string $key, mixed $value): static
    {
        $this->labels[$key] = $value;

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
     * Get Alias path
     *
     * @return string
     */
    public function getAliasPath(): string
    {
        return $this->aliasPath;
    }

    /**
     * Get Alias Params
     *
     * @return array
     */
    public function getAliasParams(): array
    {
        return $this->aliasParams;
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
     * Get Label
     *
     * Return given label value or default value if label doesn't exists
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getLabel(string $key, mixed $default): mixed
    {
        return (isset($this->labels[$key])) ? $this->labels[$key] : $default;
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
