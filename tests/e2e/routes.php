<?php

/**
 * Shared route definitions for both FPM and Swoole e2e test servers.
 *
 * Uses base Utopia\Http\Response and Utopia\Http\Request types so that
 * concrete adapter types (FPM or Swoole) can be injected at runtime.
 */

use Utopia\Http\Http;
use Utopia\Http\Response;
use Utopia\Http\Request;
use Utopia\Validator\Text;

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

Http::get('/stream')
    ->inject('response')
    ->action(function (Response $response) {
        $data = 'This is a streamed response with known size.';
        $totalSize = strlen($data);

        $response->setContentType(Response::CONTENT_TYPE_TEXT, Response::CHARSET_UTF8);
        $response->stream(
            function (int $offset, int $length) use ($data) {
                return substr($data, $offset, $length);
            },
            $totalSize
        );
    });

Http::get('/stream-large')
    ->inject('response')
    ->action(function (Response $response) {
        // Size that triggers multi-chunk streaming (CHUNK_SIZE is 2MB)
        $chunkChar = 'X';
        $totalSize = 2 * 1024 * 1024 + 512 * 1024; // 2.5MB = 2 chunks

        $response->setContentType(Response::CONTENT_TYPE_TEXT);
        $response->stream(
            function (int $offset, int $length) use ($chunkChar) {
                return str_repeat($chunkChar, $length);
            },
            $totalSize
        );
    });

Http::get('/stream-binary')
    ->inject('response')
    ->action(function (Response $response) {
        // Deterministic binary content for reproducible tests
        $data = str_repeat("\x00\xFF\xAB\xCD", 256); // 1024 bytes
        $totalSize = strlen($data);

        $response->setContentType('application/octet-stream');
        $response->addHeader('Content-Disposition', 'attachment; filename="test.bin"');
        $response->stream(
            function (int $offset, int $length) use ($data) {
                return substr($data, $offset, $length);
            },
            $totalSize
        );
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
