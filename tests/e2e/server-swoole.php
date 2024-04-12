<?php

require_once __DIR__.'/init.php';

use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Utopia\DI\Container;
use Utopia\DI\Dependency;
use Utopia\Http\Request;
use Utopia\Http\Adapter\Swoole\Server;
use Utopia\Http\Http;

$pool = new PDOPool((new PDOConfig)
    ->withHost('mariadb')
    ->withPort(3306)
    // ->withUnixSocket('/tmp/mysql.sock')
    ->withDbName('test')
    ->withCharset('utf8mb4')
    ->withUsername('user')
    ->withPassword('password')
, 9000);

$container = new Container();

$dependency = new Dependency();

$dependency
    ->setName('key')
    ->inject('request')
    ->setCallback(function (Request $request) {
        return $request->getHeader('x-utopia-key', 'unknown');
    });

$container->set($dependency);

$dependency1 = new Dependency();
$dependency1
    ->setName('pool')
    ->setCallback(function () use ($pool) {
        return $pool;
    });

$container->set($dependency1);

$server = new Server('0.0.0.0', '80');
$http = new Http($server, $container, 'UTC');

echo "Server started\n";

$http->start();
