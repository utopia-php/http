<?php

require_once __DIR__.'/init.php';

use Utopia\DI\Container;
use Utopia\Http\Adapter\FPM\Server;
use Utopia\Http\Http;

$server = new Server();
$http = new Http($server, new Container(), 'UTC');
$http->start();
