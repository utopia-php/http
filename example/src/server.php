<?php

require_once __DIR__.'/../vendor/autoload.php';

use Utopia\DI\Container;
use Utopia\DI\Dependency;
use Utopia\Http\Http;
use Utopia\Http\Response;
use Utopia\Http\Adapter\Swoole\Server;
use Utopia\Http\Validator\Text;


class User {
    function __construct(public $name) {}
}

$container = new Container();

$user = new Dependency();
$user->setName('user')->setCallback(fn() => new User("Demo user"));
$container->set($user);

Http::get('/')
    ->param('name', 'World', new Text(256), 'Name to greet. Optional, max length 256.', true)
    ->inject('response')
    ->action(function (string $name, Response $response) {
        $response->send('Hello ' . $name);
    });

Http::get('/user')
    ->inject('response')
    ->inject('user')
    ->action(function (Response $response, User $user) {
        $response->send('Hello ' . $user->name);
    });

$http = new Http(new Server('0.0.0.0', '80'), $container,'America/New_York');
$http->start();
