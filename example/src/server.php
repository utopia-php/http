<?php

require_once __DIR__.'/../vendor/autoload.php';

use Utopia\Http\Http;
use Utopia\Http\Response;
use Utopia\Http\Adapter\Swoole\Server;
use Utopia\Http\Validator\Text;

Http::get('/')
    ->param('name', 'World', new Text(256), 'Name to greet. Optional, max length 256.', true)
    ->dependency('response')
    ->action(function (string $name, Response $response) {
        $response->send('Hello ' . $name);
    });

$http = new Http(new Server('0.0.0.0', '80'), 'America/New_York');
$http->start();
