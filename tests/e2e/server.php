<?php

require_once __DIR__.'/../../vendor/autoload.php';

use Utopia\Http;
use Utopia\Adapter\FPM\Request;
use Utopia\Adapter\FPM\Response;
use Utopia\Validator\Text;

ini_set('memory_limit', '1024M');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('display_socket_timeout', '-1');
error_reporting(E_ALL);

Http::get('/')
    ->inject('response')
    ->action(function (Response $response) {
        $response->send('Hello World!');
    });

Http::get('/value/:value')
    ->param('value', '', new Text(64))
    ->inject('response')
    ->action(function (string $value, Response $response) {
        $response->send($value);
    });

Http::get('/cookies')
    ->inject('request')
    ->inject('response')
    ->action(function (Request $request, Response $response) {
        $response->send($request->getHeaders()['cookie'] ?? '');
    });

Http::get('/set-cookie')
    ->inject('request')
    ->inject('response')
    ->action(function (Request $request, Response $response) {
        $response->addHeader('Set-Cookie', 'key1=value1');
        $response->addHeader('Set-Cookie', 'key2=value2');
        $response->send('OK');
    });

Http::get('/set-cookie-no-override')
    ->inject('request')
    ->inject('response')
    ->action(function (Request $request, Response $response) {
        $response->addHeader('Set-Cookie', 'key1=value1', override: false);
        $response->addHeader('Set-Cookie', 'key2=value2', override: false);
        $response->send('OK');
    });

Http::get('/chunked')
    ->inject('response')
    ->action(function (Response $response) {
        foreach (['Hello ', 'World!'] as $key => $word) {
            $response->chunk($word, $key == 1);
        }
    });

Http::get('/redirect')
    ->inject('response')
    ->action(function (Response $response) {
        $response->redirect('/');
    });

Http::get('/humans.txt')
    ->inject('response')
    ->action(function (Response $response) {
        $response->noContent();
    });

Http::post('/functions/deployment')
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

Http::post('/databases/:databaseId/collections/:collectionId')
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
Http::init()
    ->groups(['early-response'])
    ->inject('response')
    ->action(function (Response $response) use ($earlyResponseAction) {
        $response->send('Init response. Actioned before: ' . $earlyResponseAction);
    });

Http::get('/early-response')
    ->groups(['early-response'])
    ->inject('response')
    ->action(function (Response $response) use (&$earlyResponseAction) {
        $earlyResponseAction = 'yes';
        $response->send('Action response');
    });

$request = new Request();
$response = new Response();

$app = new Http('UTC');
$app->setCompression(true);
$app->run($request, $response);
