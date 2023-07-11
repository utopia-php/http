<?php

namespace Utopia\Adapter\FPM;

use Utopia\Adapter;
use Utopia\Request as UtopiaRequest;
use Utopia\Response as UtopiaResponse;

class Server extends Adapter
{
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
