<?php

namespace Utopia\Http\Adapter\FPM;

use Utopia\Http\Adapter;
use Utopia\Http\Http;

class Server extends Adapter
{
    public function __construct()
    {
    }

    public function onRequest(callable $callback)
    {
        $request = new Request();
        $response = new Response();

        call_user_func($callback, $request, $response, 'fpm');
    }

    public function onStart(callable $callback)
    {
        call_user_func($callback, $this);
    }

    public function start()
    {
        return;
    }
}
