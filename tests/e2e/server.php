<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Utopia\App;
use Utopia\Request;
use Utopia\Response;
use Utopia\Validator\Text;

ini_set('memory_limit', '512M');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('display_socket_timeout', -1);
error_reporting(E_ALL);

App::init(function($args) {
    /** @var \Utopia\Args $args */

    $args->set(\array_merge($args->get(), [
        'customAppendedKey' => 'Hello World!'
    ]));
}, ['args']);

App::get('/')
    ->inject('response')
    ->action(function ($response) {
        $response->send('Hello World!');
    });

App::get('/params-test')
    ->param("customAppendedKey", "Empty", new Text(1024))
    ->inject('response')
    ->action(function ($customAppendedKey, $response) {
        $response->send($customAppendedKey);
    });

App::get('/chunked')
    ->inject('response')
    ->action(function ($response) {
        /** @var Utopia/Response $response */
        foreach (["Hello ", "World!"] as $key => $word) {
            $response->chunk($word, $key == 1);
        }
    });

App::get('/redirect')
    ->inject('response')
    ->action(function($response) {
        /** @var Utopia/Response $response */
        $response->redirect('/');
    });

$request    = new Request();
$response   = new Response();

$app = new App('UTC');
$app->run($request, $response);
