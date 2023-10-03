<?php

require_once __DIR__.'/init.php';

use Utopia\Http\Adapter\Swoole\Server;
use Utopia\Http\Http;

use function Swoole\Coroutine\run;

$server = new Server('0.0.0.0', '80');
$http = new Http($server, 'UTC');

run(function () use ($http) {
    $http->start();
});
