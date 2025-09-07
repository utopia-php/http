<?php

require_once __DIR__.'/../../vendor/autoload.php';

use Utopia\App;
use Utopia\Request;
use Utopia\Response;
use Utopia\Validator\Text;

ini_set('memory_limit', '1024M');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('display_socket_timeout', '-1');
error_reporting(E_ALL);

App::get('/')
    ->inject('response')
    ->action(function (Response $response) {
        $response->send('Hello World!');
    });

App::get('/value/:value')
    ->param('value', '', new Text(64))
    ->inject('response')
    ->action(function (string $value, Response $response) {
        $response->send($value);
    });

App::get('/cookies')
    ->inject('request')
    ->inject('response')
    ->action(function (Request $request, Response $response) {
        $response->send($request->getHeaders()['cookie'] ?? '');
    });

App::get('/set-cookie')
    ->inject('request')
    ->inject('response')
    ->action(function (Request $request, Response $response) {
        $response->addHeader('Set-Cookie', 'key1=value1');
        $response->addHeader('Set-Cookie', 'key2=value2');
        $response->send('OK');
    });

App::get('/chunked')
    ->inject('response')
    ->action(function (Response $response) {
        foreach (['Hello ', 'World!'] as $key => $word) {
            $response->chunk($word, $key == 1);
        }
    });

App::get('/redirect')
    ->inject('response')
    ->action(function (Response $response) {
        $response->redirect('/');
    });

App::get('/humans.txt')
    ->inject('response')
    ->action(function (Response $response) {
        $response->noContent();
    });

App::post('/functions/deployment')
    ->alias('/functions/deployment/:deploymentId')
    ->param('deploymentId', '', new Text(64, 0), '', true)
    ->inject('response')
    ->action(function (string $deploymentId, Response $response) {
        if (empty($deploymentId)) {
            $response->noContent();
            return;
        }

        $response->send('ID:' . $deploymentId);
    });

App::post('/databases/:databaseId/collections/:collectionId')
    ->alias('/database/collections/:collectionId')
    ->param('databaseId', '', new Text(64, 0), '', true)
    ->param('collectionId', '', new Text(64, 0), '', true)
    ->inject('response')
    ->action(function (string $databaseId, string $collectionId, Response $response) {
        $response->send($databaseId . ';' . $collectionId);
    });

// Endpoints for early response
// Meant to run twice, so init hook can know if action ran
$earlyResponseAction = 'no';
App::init()
    ->groups(['early-response'])
    ->inject('response')
    ->action(function (Response $response) use ($earlyResponseAction) {
        $response->send('Init response. Actioned before: ' . $earlyResponseAction);
    });

App::get('/early-response')
    ->groups(['early-response'])
    ->inject('response')
    ->action(function (Response $response) use (&$earlyResponseAction) {
        $earlyResponseAction = 'yes';
        $response->send('Action response');
    });

$request = new Request();
$response = new Response();

$app = new App('UTC');
$app->setCompression(true);
$app->run($request, $response);
