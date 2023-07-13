<?php

namespace Utopia\Http\Adapter\FPM;

use Utopia\Http\Adapter;

class Server extends Adapter
{
    public function __construct()
    {
    }

    public function onRequest(callable $callback)
    {
        call_user_func($callback, new Request(), new Response());
    }

    public function onStart(callable $callback)
    {
        call_user_func($callback, $this);
    }

    public function onWorkerStart(callable $callback)
    {
        return;
    }

    public function start()
    {
        return;
    }
}
