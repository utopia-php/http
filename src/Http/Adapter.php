<?php

namespace Utopia\Http;

abstract class Adapter
{
    abstract public function onStart(callable $callback);
    abstract public function onRequest(callable $callback);
    abstract public function start();
}
