<?php
require_once __DIR__.'/init.php';

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

$server = new Server('0.0.0.0', '80');
$http = new Http($server, $container, 'UTC');

echo "Server started\n";

$http->start();