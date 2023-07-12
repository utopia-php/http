<?php

require_once __DIR__.'/init.php';

use Utopia\Http\Adapter\FPM\Server;
use Utopia\Http\Http;

$server = new Server();
$http = new Http($server, 'UTC');
$http->start();
