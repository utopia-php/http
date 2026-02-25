<?php

require_once __DIR__.'/../../vendor/autoload.php';

use Utopia\Http\Http;
use Utopia\Http\Adapter\FPM\Request;
use Utopia\Http\Adapter\FPM\Response;

ini_set('memory_limit', '1024M');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('display_socket_timeout', '-1');
error_reporting(E_ALL);

require_once __DIR__.'/routes.php';

$request = new Request();
$response = new Response();

$app = new Http('UTC');
$app->setCompression(true);
$app->run($request, $response);
