<?php

require_once __DIR__.'/init.php';

use Utopia\Adapter\FPM\Server;
use Utopia\Http;

$server = new Server();
$http = new Http($server, 'UTC');
$http->start();
