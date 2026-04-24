<?php

declare(strict_types=1);

namespace Utopia\Http;

use Utopia\Servers\Hook;

/**
 * Process-global hook registry.
 *
 * Owns the lifecycle-hook arrays (init/shutdown/options/error/start/request)
 * that used to live on {@see Http} as protected statics. Keeping them here
 * means {@see Dispatcher} can read the registries through a dedicated
 * primitive instead of forcing {@see Http} to expose six `getXxxHooks()`
 * accessors purely for internal consumption.
 *
 * Hooks are populated at bootstrap and must not be mutated after the
 * server starts accepting requests; registration APIs are public static
 * methods, reads are public static arrays.
 */
final class Hooks
{
    /** @var Hook[] */
    public static array $init = [];

    /** @var Hook[] */
    public static array $shutdown = [];

    /** @var Hook[] */
    public static array $options = [];

    /** @var Hook[] */
    public static array $errors = [];

    /** @var Hook[] */
    public static array $start = [];

    /** @var Hook[] */
    public static array $request = [];

    /**
     * Register a callback that runs before the matched route action.
     */
    public static function init(): Hook
    {
        $hook = new Hook();
        $hook->groups(['*']);
        self::$init[] = $hook;

        return $hook;
    }

    /**
     * Register a callback that runs after the matched route action.
     */
    public static function shutdown(): Hook
    {
        $hook = new Hook();
        $hook->groups(['*']);
        self::$shutdown[] = $hook;

        return $hook;
    }

    /**
     * Register a callback for OPTIONS method requests.
     */
    public static function options(): Hook
    {
        $hook = new Hook();
        $hook->groups(['*']);
        self::$options[] = $hook;

        return $hook;
    }

    /**
     * Register an error callback.
     */
    public static function error(): Hook
    {
        $hook = new Hook();
        $hook->groups(['*']);
        self::$errors[] = $hook;

        return $hook;
    }

    /**
     * Register a callback that runs once when the server starts.
     */
    public static function onStart(): Hook
    {
        $hook = new Hook();
        self::$start[] = $hook;

        return $hook;
    }

    /**
     * Register a callback that runs at the top of every request, before
     * route matching.
     */
    public static function onRequest(): Hook
    {
        $hook = new Hook();
        self::$request[] = $hook;

        return $hook;
    }

    /**
     * Clear every registered hook. Intended for test isolation.
     */
    public static function reset(): void
    {
        self::$init = [];
        self::$shutdown = [];
        self::$options = [];
        self::$errors = [];
        self::$start = [];
        self::$request = [];
    }
}
