<?php

namespace Utopia\Adapter\FPM;

use Utopia\Adapter\Adapter;
use Utopia\Http;

class Server extends Adapter
{
    public function __construct()
    {
    }

    public function onRequest(callable $callback)
    {
        $request = new Request();
        $response = new Response();

        Http::setResource('fpmRequest', fn () => $request);
        Http::setResource('fpmResponse', fn () => $response);

        call_user_func($callback, $request, $response);
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
