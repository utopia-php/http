<?php

require_once __DIR__.'/../../vendor/autoload.php';

use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Utopia\DI\Container;
use Utopia\Http\Request;
use Utopia\Http\Adapter\SwooleCoroutine\Server;
use Utopia\Http\Http;

$container = new Container();

require_once __DIR__.'/init.php';

$pool = new PDOPool((new PDOConfig())
    ->withHost('mariadb')
    ->withPort(3306)
    // ->withUnixSocket('/tmp/mysql.sock')
    ->withDbName('test')
    ->withCharset('utf8mb4')
    ->withUsername('user')
    ->withPassword('password'), 9000);


$container->set('key', function (Request $request) {
    return $request->getHeader('x-utopia-key', 'unknown');
}, ['request']);

$container->set('pool', function () use ($pool) {
    return $pool;
});

$server = new Server('0.0.0.0', '80');
$http = new Http($server, $container, 'UTC');

echo "Server started\n";

$http->start();
