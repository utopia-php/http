<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Utopia\Http\Http;
use Utopia\Http\Adapter\Swoole\Request;
use Utopia\Http\Adapter\Swoole\Response;
use Utopia\Http\Adapter\Swoole\Server;
use Utopia\Validator\Text;

ini_set('memory_limit', '1024M');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
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

// ── Streaming endpoints ──

Http::get('/stream/generator')
    ->inject('response')
    ->action(function (Response $response) {
        $chunks = ['chunk1-', 'chunk2-', 'chunk3'];
        $totalSize = array_sum(array_map('strlen', $chunks));

        $generator = (function () use ($chunks) {
            foreach ($chunks as $chunk) {
                yield $chunk;
            }
        })();

        $response->stream($generator, $totalSize);
    });

Http::get('/stream/callable')
    ->inject('response')
    ->action(function (Response $response) {
        $data = str_repeat('A', 1000);

        $response->stream(function (int $offset, int $length) use ($data) {
            return substr($data, $offset, $length);
        }, strlen($data));
    });

Http::get('/stream/generator-large')
    ->inject('response')
    ->action(function (Response $response) {
        $chunkSize = 100000; // 100KB per chunk
        $numChunks = 5;
        $totalSize = $chunkSize * $numChunks;

        $generator = (function () use ($chunkSize, $numChunks) {
            for ($i = 0; $i < $numChunks; $i++) {
                yield str_repeat(chr(65 + $i), $chunkSize);
            }
        })();

        $response->stream($generator, $totalSize);
    });

Http::get('/stream/non-detach/generator')
    ->inject('response')
    ->action(function (Response $response) {
        $response->setDetach(false);

        $chunks = ['nd-chunk1-', 'nd-chunk2-', 'nd-chunk3'];
        $totalSize = array_sum(array_map('strlen', $chunks));

        $generator = (function () use ($chunks) {
            foreach ($chunks as $chunk) {
                yield $chunk;
            }
        })();

        $response->stream($generator, $totalSize);
    });

Http::get('/stream/non-detach/callable')
    ->inject('response')
    ->action(function (Response $response) {
        $response->setDetach(false);

        $data = str_repeat('B', 1000);

        $response->stream(function (int $offset, int $length) use ($data) {
            return substr($data, $offset, $length);
        }, strlen($data));
    });

$server = new Server('0.0.0.0', '80');
$app = new Http('UTC');
$app->setCompression(true);
$server->onStart(function () {
    echo 'Swoole server started on port 80' . PHP_EOL;
});
$server->onRequest(function (Request $request, Response $response) use ($app) {
    $app->run($request, $response);
});
$server->start();
