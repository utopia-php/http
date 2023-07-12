<?php

require_once __DIR__.'/init.php';

use Utopia\Adapter\Swoole\Server;
use Utopia\App;

$server = new Server('0.0.0.0', '80');
$app = new App($server, 'UTC');

$server->onWorkerStart(function ($swooleServer, $workerId) {
    \fwrite(STDOUT, "Worker " . ++$workerId . " started successfully\n");
});

$app->start();
