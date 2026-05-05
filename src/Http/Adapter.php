<?php

declare(strict_types=1);

namespace Utopia\Http;

use Utopia\DI\Container;

abstract class Adapter
{
    abstract public function onStart(callable $callback): void;
    abstract public function onRequest(callable $callback): void;
    abstract public function start(): void;
    abstract public function getContext(): Container;
}
