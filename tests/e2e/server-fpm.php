<?php

use Utopia\DI\Container;
use Utopia\Http\Adapter\FPM\Server;
use Utopia\Http\Http;

require_once __DIR__.'/../../vendor/autoload.php';

$container = new Container();

require_once __DIR__.'/init.php';


$server = new Server();
$http = new Http($server, $container, 'UTC');
$http->start();
