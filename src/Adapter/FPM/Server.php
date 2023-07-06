<?php

namespace Utopia\Adapter\FPM;

use Utopia\Adapter;
use Swoole\Http\Server as SwooleServer;
use Utopia\Request as UtopiaRequest;
use Utopia\Response as UtopiaResponse;


class Server extends Adapter {

    protected SwooleServer $server;

    public function __construct()
    {
    }

    public function getRequest(): UtopiaRequest
    {
        return new Request();
    }

    public function getResponse(): UtopiaResponse
    {
        return new Response();
    }

}