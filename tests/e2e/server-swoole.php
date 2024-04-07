<?php
require_once __DIR__.'/init.php';

use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Runtime;
use Utopia\DI\Container;
use Utopia\DI\Dependency;
use Utopia\Http\Request;
use Utopia\Http\Adapter\Swoole\Server;
use Utopia\Http\Http;

Runtime::enableCoroutine(SWOOLE_HOOK_ALL);

$container = new Container();

$dependency = new Dependency();

$dependency
    ->setName('key')
    ->dependency('request')
    ->setCallback(function (Request $request) {
        return $request->getHeader('x-utopia-key', 'unknown');
    });

$container->set($dependency);

Http::delete('/swoole-test')
    ->dependency('swooleRequest')
    ->dependency('swooleResponse')
    ->action(function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse) {
        $method = $swooleRequest->getMethod();
        $swooleResponse->header('Content-Type', 'text/plain');
        $swooleResponse->header('Cache-Control', 'no-cache');
        $swooleResponse->setStatusCode(200);
        $swooleResponse->write($method);
        $swooleResponse->end();
    });

$server = new Server('0.0.0.0', '80');
$http = new Http($server, $container, 'UTC');

echo "Server started\n";

$http->start();