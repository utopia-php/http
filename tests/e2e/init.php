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
    ->dependency('response')
    ->action(function ($response) {
        $response->send('WILDCARD');
    });

Http::get('/')
    ->dependency('response')
    ->action(function (Response $response) {
        $response->send('Hello World!');
    });

Http::get('/headers')
    ->dependency('response')
    ->action(function (Response $response) {
        $response
            ->addHeader('key1', 'value1')
            ->addHeader('key2', 'value2')
            ->send('Hello World!');
    });

Http::get('/keys')
    ->dependency('response')
    ->dependency('key')
    ->action(function (Response $response, string $key) {
        if (rand(0, 50) == 1) {
            System::sleep(1);
        }

        $response->send($key);
    });

Http::get('/value/:value')
    ->param('value', '', new Text(64))
    ->dependency('response')
    ->action(function (string $value, Response $response) {
        $response->send($value);
    });

Http::get('/chunked')
    ->dependency('response')
    ->action(function (Response $response) {
        foreach (['Hello ', 'World!'] as $key => $word) {
            $response->chunk($word, $key == 1);
        }
    });

Http::get('/redirect')
    ->dependency('response')
    ->action(function (Response $response) {
        $response->redirect('/');
    });

Http::get('/humans.txt')
    ->dependency('response')
    ->action(function (Response $response) {
        $response->noContent();
    });

Http::delete('/no-content')
    ->dependency('response')
    ->action(function (Response $response) {
        $response->noContent();
    });

Http::error()
    ->dependency('error')
    ->dependency('response')
    ->action(function (Throwable $error, Response $response) {
        $response->send($error->getMessage().' on file: '.$error->getFile().' on line: '.$error->getLine());
    });
