<?php

require_once __DIR__.'/init.php';

use Utopia\Adapter\Swoole\Request;
use Utopia\Adapter\Swoole\Response;
use Utopia\Adapter\Swoole\Server;
use Utopia\App;

$server = new Server('0.0.0.0', '80');

$server->onRequest(function (Request $request, Response $response) use ($server) {
    $app = new App($server, 'UTC');
    $app->run($request, $response);
});

$server->onWorkerStart(function ($swooleServer, $workerId) {
    \fwrite(STDOUT, "Worker " . ++$workerId . " started successfully\n");
});

$server->start();
