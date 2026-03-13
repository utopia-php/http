<?php

namespace Utopia\Http;

use Utopia\DI\Container;

abstract class Adapter
{
    abstract public function onStart(callable $callback);
    abstract public function onRequest(callable $callback);
    abstract public function start();
    abstract public function getContainer(): Container;
}
