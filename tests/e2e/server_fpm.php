<?php

require_once __DIR__.'/init.php';

use Utopia\Adapter\FPM\Server;
use Utopia\App;

$server = new Server();

$app = new App($server, 'UTC');

$server->onRequest(function ($request, $response) use ($app) {
    $app->run($request, $response);
});
