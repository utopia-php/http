<?php

require_once __DIR__.'/init.php';

use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Utopia\Http\Adapter\Swoole\Server;
use Utopia\Http\Http;

use function Swoole\Coroutine\run;

Http::delete('/swoole-test')
    ->inject('swooleRequest')
    ->inject('swooleResponse')
    ->action(function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse) {
        $method = $swooleRequest->getMethod();
        $swooleResponse->header('Content-Type', 'text/plain');
        $swooleResponse->header('Cache-Control', 'no-cache');
        $swooleResponse->setStatusCode(200);
        $swooleResponse->write($method);
        $swooleResponse->end();
    });

$server = new Server('0.0.0.0', '80');
$http = new Http($server, 'UTC');

run(function () use ($http) {
    $http->start();
});
