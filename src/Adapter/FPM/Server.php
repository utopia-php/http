<?php

namespace Utopia\Adapter\FPM;

use Utopia\Adapter;

class Server extends Adapter
{
    public function __construct()
    {
        parent::__construct();
    }

    public function onRequest(callable $callback)
    {
        call_user_func($callback, new Request(), new Response());
    }

    public function start()
    {
        return;
    }
}
