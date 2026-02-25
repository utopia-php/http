<?php

require_once __DIR__.'/../../vendor/autoload.php';

use Utopia\Http\Http;
use Utopia\Http\Adapter\Swoole\Request;
use Utopia\Http\Adapter\Swoole\Response;
use Utopia\Http\Adapter\Swoole\Server;

ini_set('memory_limit', '1024M');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__.'/routes.php';

$server = new Server('0.0.0.0', '8080');

$server->onRequest(function (Request $request, Response $response) {
    $app = new Http('UTC');
    $app->run($request, $response);
});

$server->onStart(function () {
    echo "Swoole server started on http://0.0.0.0:8080\n";
});

$server->start();
