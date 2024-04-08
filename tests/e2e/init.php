<?php

require_once __DIR__.'/../../vendor/autoload.php';

use Swoole\Coroutine\System;
use Utopia\Http\Http;
use Utopia\Http\Response;
use Utopia\Http\Validator\Text;

ini_set('memory_limit', '1024M');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('display_socket_timeout', '-1');
error_reporting(E_ALL);

Http::wildcard()
    ->inject('response')
    ->action(function ($response) {
        $response->send('WILDCARD');
    });

Http::get('/')
    ->inject('response')
    ->action(function (Response $response) {
        $response->send('Hello World!');
    });

Http::get('/headers')
    ->inject('response')
    ->action(function (Response $response) {
        $response
            ->addHeader('key1', 'value1')
            ->addHeader('key2', 'value2')
            ->send('Hello World!');
    });

Http::get('/keys')
    ->inject('response')
    ->inject('key')
    ->action(function (Response $response, string $key) {
        if (rand(0, 50) == 1) {
            System::sleep(1);
        }

        $response->send($key);
    });

Http::get('/value/:value')
    ->param('value', '', new Text(64))
    ->inject('response')
    ->action(function (string $value, Response $response) {
        $response->send($value);
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

Http::delete('/no-content')
    ->inject('response')
    ->action(function (Response $response) {
        $response->noContent();
    });

Http::error()
    ->inject('error')
    ->inject('response')
    ->action(function (Throwable $error, Response $response) {
        $response->send($error->getMessage().' on file: '.$error->getFile().' on line: '.$error->getLine());
    });
